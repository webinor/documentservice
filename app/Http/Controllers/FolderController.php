<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Models\DepartmentFolder;
use App\Models\Folder;
use App\Models\Misc\Document;
use App\Services\CheckPermissionsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolderController extends Controller
{


    protected $departmentServiceBaseUrl;
    protected $userServiceBaseUrl;

    protected CheckPermissionsService $permissionsService;

    public function __construct(CheckPermissionsService $permissionsService)
    {
        $this->permissionsService = $permissionsService;
        $this->departmentServiceBaseUrl = config(
            "services.department_service.base_url"
        );
        $this->userServiceBaseUrl = config("services.user_service.base_url");
    }
    /**
 * Display a listing of the resource.
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function index($id = null)
{
    try {
        // 🔹 Récupération de tous les dossiers


        if (!$id) {

        $folders = Folder::
        orderBy('name')
        ->whereParentId(null)
        ->with(["department_folders"])->get();
            
            return response()->json([
            'success' => true,
            'message' => 'Liste des dossiers récupérée avec succès',
            'folders' => $folders,

        ], 200);

        }

        $folders = Folder::
        orderBy('name')->
        with(["department_folders"])->get();

        $documents = Document::where('folder_id', $id)->get();


        return response()->json([
            'success' => true,
            'message' => 'Liste des dossiers récupérée avec succès',
            'folders' => $folders,
            'documents' => $documents,

        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des dossiers',
            'error' => $e->getMessage(),
        ], 500);
    }
}


        public function getFoldersWithPermissions(Request $request)
    {
        $departmentId = $request->query("departmentId"); // ?departmentId=67
        $userId = $request->query("userId"); // &userId=1015
        $folderId = $request->query("folderId"); // &folderId=1015
        $foldersWithPermissions = [];
        $fullDocuments = [];
        $documents = [];
        $documentsWithPermissions = [];
        $documentTypesWithPermissions = [];

       

        if ($folderId) {
            
               $department_folders = DepartmentFolder::whereHas("folder.parent",function($query) use($folderId){
                $query->whereId($folderId);
               })-> with(
            "folder"
        )
            ->whereDepartmentId($departmentId)
            ->get();

            $documents = Document::with('document_type')-> whereFolderId($folderId)->get();

           

            //throw new Exception(json_encode($documents), 1);
            

        } else {
            
            $department_folders = DepartmentFolder::doesntHave("folder.parent")->with(
            "folder"
        )
            ->whereDepartmentId($departmentId)
            ->get();

        }
        
     

        // Transformer en tableau simplifié
        $folders = $department_folders
        ->sortBy(fn($item) => $item->folder->name) // tri alphabétique sur le nom du dossier
            ->map(function ($item) {
                return [
                    "id" => $item->folder->id, // id du folder
                    "name" => $item->folder->name, // nom/name du document
                ];
            })
            ->toArray();

        // ⚠️ Si pas de folders, on retourne un tableau vide directement
        if (!empty($folders)) {
            
            //return response()->json([], 200);
        

        $permissionsMap = $this->permissionsService->checkPermissionsForUserAndFolders(
            $userId,
            $folders
        );

        //dd($permissionsMap);

        $foldersWithPermissions = $department_folders->map(function (
            $item
        ) use ($permissionsMap) {
            $folderId = $item->folder->id;

            return [
                "id" => $folderId,
                "name" => $item->folder->name,
                "permissions" => $permissionsMap[$folderId] ?? [], // permissions correspondantes
            ];
        });

    }

      if (!empty($documents)) {

                 $documentTypes = $documents
            ->map(function ($item) {
                return [
                    "id" => $item->document_type->id, // id du document_type
                    "type" => $item->document_type->name, // nom/type du document
                ];
            })
            ->toArray();


            $permissionsMap = $this->permissionsService->checkPermissionsForUserAndDocumentTypes(
            $userId,
            $documentTypes
        );

        //dd($permissionsMap);

        $documentTypesWithPermissions = $documents->map(function (
            $item
        ) use ($permissionsMap) {
            $documentTypeId = $item->document_type->id;

            return [
                "id" => $documentTypeId,
                "name" => $item->document_type->name,
                "permissions" => $permissionsMap[$documentTypeId] ?? [], // permissions correspondantes
            ];
        });



        $documentsWithPermissions = $this->filterDocuments($documents , $documentTypesWithPermissions);
        


      }

        // Retourner un DTO ou juste un JSON
        /* return response()->json([
            'folders' => $folders,
            'permissions' => $permissionsMap
        ]);*/

        return response()->json(["success"=>true, 'folders'=>$foldersWithPermissions , 'documents'=>$documentsWithPermissions , 'document_type'=>$documentTypesWithPermissions], 200);
    }


public function filterDocuments($documentsList , $documentTypesList)  {

    $documents = collect($documentsList);
$documentTypes = collect($documentTypesList);

// On récupère les IDs des types de document autorisés à la vue
$authorizedTypeIds = $documentTypes
    ->filter(fn($type) => !empty($type['permissions']['view']))
    ->pluck('id')
    ->toArray();

// On garde seulement les documents ayant un type autorisé
$filteredDocuments = $documents
    ->filter(fn($doc) => in_array($doc['document_type_id'], $authorizedTypeIds))
    ->values()
    ->toArray();

    return $filteredDocuments;
    
}

   /**
 * Store a newly created resource in storage.
 *
 * @param  \App\Http\Requests\StoreFolderRequest  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function store(StoreFolderRequest $request)
{
    try {
        // 🔹 Données validées
        $validated = $request->validated();
        $userConnected = $request->get("user");

        DB::beginTransaction();

        // 🔹 Création du dossier
        $folder = Folder::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'recipient_mode' => $validated['recipientMode'] ?? null,
            'created_by' => $userConnected['id'],
            'notify_allowed_user' => $validated['notify_allowed_user'] ?? null,
            
        ]);

        // 🔹 Création des liens dossier <-> départements
        if (!empty($validated['departmentIds']) && is_array($validated['departmentIds'])) {
            $departmentFolders = collect($validated['departmentIds'])->map(function ($departmentId) use ($folder) {
                return [
                    'department_id' => $departmentId,
                    'folder_id' => $folder->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DB::table('department_folders')->insert($departmentFolders);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Dossier créé avec succès',
            'folder' => $folder, // charge les relations si besoin
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création du dossier',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function getFoldersWithPermissionsForRoles(Request $request)
    {
        $departmentId = $request->query("departmentId"); // ?departmentId=67
        $roleId = $request->query("roleId"); // &roleId=1015

        $department_folders = DepartmentFolder::with(
            "folder"
        )
            ->whereDepartmentId($departmentId)
            ->get();

        // Transformer en tableau simplifié
           $folders = $department_folders
            ->map(function ($item) {
                return [
                    "id" => $item->folder->id, // id du folder
                    "name" => $item->folder->name, // nom/name du document
                ];
            })
            ->toArray();

        //⚠️ Si pas de folders, on retourne un tableau vide directement
        if (empty($folders)) {
            return response()->json([], 200);
        }

        $permissionsMap = $this->permissionsService->checkPermissionsForRoleAndFolders(
            $roleId,
            $folders
        );

        //dd($permissionsMap);

        $documentsWithPermissions = $department_folders->map(function (
            $item
        ) use ($permissionsMap) {
            $folderId = $item->folder->id;

            return [
                "id" => $folderId,
                "name" => $item->folder->name,
                "permissions" => $permissionsMap[$folderId] ?? [], // permissions correspondantes
            ];
        });

        // Retourner un DTO ou juste un JSON
        /* return response()->json([
            'folders' => $folders,
            'permissions' => $permissionsMap
        ]);*/

        return response()->json($documentsWithPermissions, 200);
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function show(Folder $folder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function edit(Folder $folder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFolderRequest  $request
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
public function update(UpdateFolderRequest $request, Folder $folder)
{
    try {
        DB::beginTransaction();


        $validated = $request->validated();
        $folder->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'notify_allowed_user' => $validated['notify_allowed_user'] ?? null,
        ]);

        if (!empty($validated['departmentIds'])) {
            // department_folders::sync() si relation pivot
           // $folder->departments()->sync($validated['departmentIds']); // impossible car department est un microservice different

            DB::table("department_folders")
                ->where("folder_id", $folder->id)
                ->delete();

            foreach ($validated["departmentIds"] as $deptId) {
                DB::table("department_folders")->insert([
                    //"code" => Str::random(20),
                    "folder_id" => $folder->id,
                    "department_id" => $deptId,
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Dossier mis à jour avec succès',
            'folder' => $folder->load('department_folders'),
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du dossier',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function destroy(Folder $folder)
    {
        //
    }
}
