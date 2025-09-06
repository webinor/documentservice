<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Models\DepartmentDocumentType;
use App\Models\Misc\DocumentType;
use App\Services\CheckPermissionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isEmpty;

class DocumentTypeController extends Controller
{
    protected $departmentServiceBaseUrl;
    protected $userServiceBaseUrl;

    protected CheckPermissionsService $permissionsService;

    public function __construct(CheckPermissionsService $permissionsService)
    {
        $this->permissionsService = $permissionsService;
        $this->departmentServiceBaseUrl = config('services.department_service.base_url');
        $this->userServiceBaseUrl = config('services.user_service.base_url');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $documentTypes = DocumentType::get();
        return response()->json(['success' => true , 'data'=> $documentTypes] );
    }


    public function getRolesByDocumentType($documentTypeCode)  {
        
        try {
            
            $documentType = DocumentType::whereId($documentTypeCode)->first();

            if (!$documentType) {
                return response()->json(['success' => true , 'data'=> [] ] );
            }
            
            $department_documentTypes = DepartmentDocumentType::whereDocumentTypeId($documentType->id)->get();

      
            if (($department_documentTypes->count() == 0)) {
                
            return response()->json(['success' => true , 'data'=> [] ] );
                

            }

            $departmentIds = array_column($department_documentTypes->toArray(), 'department_id');


            $bodySent = ["departmentIds" => $departmentIds];
            $rolesResponse = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post($this->departmentServiceBaseUrl."/getRolesByDepartments", $bodySent);

            $rolesEnrichis = [];

            
            // Vérifier si la requête a réussi
            if ($rolesResponse->successful()) {
                  $roles = $rolesResponse->json()['data'];
            
                    $bodyEnrichSent = ["documentTypes" => $documentType->pluck('id') , "rolesNames" => $roles];

                // Ensuite enrichir les rôles
                //$rolesEnrichisResponse = Http::post($this->userServiceBaseUrl."/getRolesByNames", $bodyEnrichSent);
                $rolesEnrichisResponse = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->post($this->userServiceBaseUrl."/getPermissionsByRolesNames", $bodyEnrichSent);
            
                if ($rolesEnrichisResponse->successful()) {
                    $rolesEnrichis = $rolesEnrichisResponse->json();
                    //return $rolesEnrichis;
                } else {
                    // Gérer l'erreur de la deuxième requête
                    return response()->json([
                        "url"=>$this->userServiceBaseUrl."/getRolesByNames",
                        "error" => "Erreur lors de l'enrichissement des rôles",
                        "status" => $rolesEnrichisResponse->status(),
                        "body_sent" => $bodyEnrichSent,
                        "response_body" => $rolesEnrichisResponse->body()
                    ], $rolesEnrichisResponse->status());
                }
            } else {
                // Gérer l'erreur de la première requête
                return response()->json([
                    "error" => "Erreur lors de la récupération des rôles",
                    "status" => $rolesResponse->status(),
                    "body" => $rolesResponse->body(),
                    "body_sent" => $bodySent,
                    "response_body" => $rolesResponse->body()
                    
                ], $rolesResponse->status());
            }
            
            return response()->json(['success' => true , 'data'=> $rolesEnrichis] );

        } catch (\Throwable $th) {
            throw $th;
        }
       

    }
    
    public function getDocumentTypesWithPermissions(Request $request)
    {

        $departmentId = $request->query('departmentId'); // ?departmentId=67
        $userId = $request->query('userId');           // &userId=1015

        $department_document_types = DepartmentDocumentType::with('document_type')-> whereDepartmentId($departmentId)->get();

        // Transformer en tableau simplifié
 $documents = $department_document_types->map(function($item) {
    return [
        'id' => $item->document_type->id,   // id du document_type
        'type' => $item->document_type->name // nom/type du document
    ];
})->toArray();


    // ⚠️ Si pas de documents, on retourne un tableau vide directement
    if (empty($documents)) {
        return response()->json([], 200);
    }


   $permissionsMap = $this->permissionsService->checkPermissionsForUserAndDocumentTypes($userId, $documents);

//dd($permissionsMap);

    $documentsWithPermissions = $department_document_types->map(function($item) use ($permissionsMap) {
        $documentId = $item->document_type->id;
    
       

        return [
            'id' => $documentId,
            'name' => $item->document_type->name,
            'permissions' => $permissionsMap[$documentId] ?? []  // permissions correspondantes
        ];
    });

   
      
    
        // Retourner un DTO ou juste un JSON
       /* return response()->json([
            'documents' => $documents,
            'permissions' => $permissionsMap
        ]);*/

        return response()->json($documentsWithPermissions , 200);
    }

    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreDocumentTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\DocumentType  $documentType
     * @return \Illuminate\Http\Response
     */
    public function show(DocumentType $documentType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Misc\DocumentType  $documentType
     * @return \Illuminate\Http\Response
     */
    public function edit(DocumentType $documentType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentTypeRequest  $request
     * @param  \App\Models\Misc\DocumentType  $documentType
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Misc\DocumentType  $documentType
     * @return \Illuminate\Http\Response
     */
    public function destroy(DocumentType $documentType)
    {
        //
    }
}
