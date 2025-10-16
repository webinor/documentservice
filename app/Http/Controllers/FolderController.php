<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Folder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Misc\Document;
use App\Models\DepartmentFolder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\StoreFolderRequest;
use App\Services\CheckPermissionsService;
use App\Http\Requests\UpdateFolderRequest;

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
        $documents = collect();
        $documentsWithPermissions = [];
        $documentTypesWithPermissions = [];
        $current_folder = null;

       

        if ($folderId) {

            $current_folder = Folder::find($folderId);
            
               $department_folders = DepartmentFolder::whereHas("folder.parent",function($query) use($current_folder){
                $query->whereId($current_folder->id);
               })-> with(
            "folder"
        )
            ->whereDepartmentId($departmentId)
            ->get();

           /* if (!$department_folders) {

            $current_folder = Folder::find($current_folder->parent_id);


                 $department_folders = DepartmentFolder::whereHas("folder.parent",function($query) use($current_folder){
                $query->whereId($current_folder->id);
               })-> with(
            "folder"
        )
            ->whereDepartmentId($departmentId)
            ->get();
                
                
            } */

            $documents = Document::with(['document_type','main_attachment.file'])-> whereFolderId($folderId)->get();

           

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
                    "parent_id"=>$item->folder->parent_id,
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
                "created_at"=> $item->folder->created_at,
                "notify_allowed_user"=>$item->folder->notify_allowed_user,
                "permissions" => $permissionsMap[$folderId] ?? [], // permissions correspondantes
            ];
        });

    }

      if (!$documents->isEmpty()) {

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


      $foldersAndDocuments=  $this->mergeFoldersAndDocuments($foldersWithPermissions , $documentsWithPermissions);
      


      return response()->json([
    'success' => true,
    'pathSegments'=> $current_folder ? $current_folder->getPathSegments() : [],
    'items' => $foldersAndDocuments,
]);


        // Retourner un DTO ou juste un JSON
        /* return response()->json([
            'folders' => $folders,
            'permissions' => $permissionsMap
        ]);*/

        return response()->json(["success"=>true, 'folders'=>$foldersWithPermissions , 'documents'=>$documentsWithPermissions , 'document_type'=>$documentTypesWithPermissions], 200);
    }
public function mergeFoldersAndDocuments($foldersWithPermissions, $documentsWithPermissions)
{
    $items = collect([]);

    // --- Dossiers ---
    foreach ($foldersWithPermissions as $folder) {
        $items->push([
            'id' => $folder['id'],
            'name' => $folder['name'],
            'type' => 'folder',
            'attachment_icon' => '📁',
            'attachment_slug' => 'Dossier',
            'should_notify' => $folder['notify_allowed_user'] ? '✅' : '',
            'date_creation' => $folder['created_at'],
            'permissions' => $folder['permissions'],
        ]);
    }

    // --- Documents ---
    foreach ($documentsWithPermissions as $document) {
        $attachmentType = $document['main_attachment']['file']['type'] ?? null;
        $attachmentIcon = '📄';
        $attachmentSlug = 'autre';

        if ($attachmentType) {
            switch (true) {
                case str_contains($attachmentType, 'pdf'):
                    $attachmentIcon = '📕';
                    $attachmentSlug = 'pdf';
                    break;
                case str_contains($attachmentType, 'image'):
                    $attachmentIcon = '🖼️';
                    $attachmentSlug = 'image';
                    break;
                case str_contains($attachmentType, 'word'):
                case str_contains($attachmentType, 'msword'):
                case str_contains($attachmentType, 'officedocument.wordprocessingml'):
                    $attachmentIcon = '📘';
                    $attachmentSlug = 'word';
                    break;
                case str_contains($attachmentType, 'excel'):
                case str_contains($attachmentType, 'spreadsheet'):
                    $attachmentIcon = '📗';
                    $attachmentSlug = 'excel';
                    break;
                case str_contains($attachmentType, 'powerpoint'):
                case str_contains($attachmentType, 'presentation'):
                    $attachmentIcon = '📙';
                    $attachmentSlug = 'powerpoint';
                    break;
                case str_contains($attachmentType, 'zip'):
                case str_contains($attachmentType, 'compressed'):
                    $attachmentIcon = '🗜️';
                    $attachmentSlug = 'zip';
                    break;
                case str_contains($attachmentType, 'text'):
                    $attachmentIcon = '📄';
                    $attachmentSlug = 'text';
                    break;
                case str_contains($attachmentType, 'audio'):
                    $attachmentIcon = '🎵';
                    $attachmentSlug = 'audio';
                    break;
                case str_contains($attachmentType, 'video'):
                    $attachmentIcon = '🎬';
                    $attachmentSlug = 'video';
                    break;
            }
        }

        $items->push([
            'id' => $document['id'],
            'name' => $document['title'],
            'type' => 'document',
            'attachment_type' => $attachmentType,
            'attachment_icon' => $attachmentIcon,
            'attachment_slug' => Str::headline($attachmentSlug),
            'permissions' => $document['permissions'],
            'date_creation' => $document['created_at'],
            'view_url' => config('services.frontend_service.base_url') . "/details-du-document/{$document['id']}",
            'download_url' => route('documents.download', ['id' => $document['id']]),
        ]);
    }

    // --- Grouper par type, puis trier chaque groupe par nom ---
    $grouped = $items
        ->groupBy('type') // regroupe 'folder' et 'document'
        ->map(function ($group) {
            return $group->sortBy('name')->values(); // trie les noms dans chaque groupe
        });

    // --- Si tu veux un seul tableau fusionné avec dossiers d’abord ---
    $merged = $grouped->get('folder', collect())
        ->merge($grouped->get('document', collect()))
        ->values();

    return $merged;
}


public function filterDocuments($documentsList , $documentTypesList) 
{

    $documents = collect($documentsList);
$documentTypes = collect($documentTypesList);

// On indexe les permissions par id du document_type pour un accès rapide
$permissionsByType = collect($documentTypes)
    ->mapWithKeys(fn($type) => [$type['id'] => $type['permissions']]);

// On récupère les IDs des types de document autorisés à la vue
$authorizedTypeIds = $documentTypes
    ->filter(fn($type) => !empty($type['permissions']['view']))
    ->pluck('id')
    ->toArray();

// On garde seulement les documents dont le type est autorisé
$filteredDocuments = collect($documents)
    ->filter(fn($doc) => in_array($doc['document_type_id'], $authorizedTypeIds))
    ->map(function ($doc) use ($permissionsByType) {
        $doc['permissions'] = $permissionsByType[$doc['document_type_id']] ?? [
            'create' => false,
            'view' => false,
            'validate' => false,
            'delete' => false,
            'reject' => false,
            'forward' => false,
        ];
        return $doc;
    })
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
            'parent_id' => $validated['parent_id'] ?? null,
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
                "parent_id"=>$item->folder->parent_id,
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
     * 🔹 Endpoint : Récupère les départements autorisés à gérer un sous-dossier.
     * 
     * GET /api/folders/{parentFolderId}/authorized-departments
     */
    public function getAuthorizedDepartments(Request $request, $folderId)
    {
        try {
            // 🗂️ Vérifie si le dossier existe
            $folder = Folder::find($folderId);
            if (!$folder) {
                return response()->json([
                    'success' => false,
                    'message' => "Le dossier avec l'ID {$folderId} est introuvable.",
                ], 404);
            }

            // 🧭 Détermine si c’est un dossier racine ou non
            $targetFolderId = $folder->parent_id ?: $folder->id;

            // 🔍 Récupère les départements autorisés sur le dossier cible (parent ou dossier actuel)
            $allowedDepartmentIds = DepartmentFolder::where('folder_id', $targetFolderId)
                ->pluck('department_id')
                ->toArray();

            if (empty($allowedDepartmentIds)) {
                return response()->json([
                    'success' => true,
                    'departments' => [],
                    'message' => "Aucun département n’a accès à ce dossier.",
                ], 200);
            }

            // 🌐 Appel au microservice des départements
            $departmentServiceUrl = config('services.department_service.base_url') . '/list-by-ids';
            
            $response = Http::withToken($request->bearerToken())
                ->post($departmentServiceUrl, [
                    'ids' => $allowedDepartmentIds,
                ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de contacter le microservice des départements.',
                    'error' => $response->body(),
                ], 500);
            }

            $departments = $response->json('data') ?? [];

            return response()->json([
                'success' => true,
                'folder' => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'is_root' => $folder->parent_id === null,
                ],
                'departments' => $departments,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des départements autorisés.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }





    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function show(Folder $folder)
    {
        return response()->json(
            $folder
        );
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
