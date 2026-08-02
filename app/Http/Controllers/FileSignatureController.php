<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileSignatureRequest;
use App\Http\Requests\UpdateFileSignatureRequest;
use App\Models\FileSignature;

class FileSignatureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Http\Requests\StoreFileSignatureRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFileSignatureRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FileSignature  $fileSignature
     * @return \Illuminate\Http\Response
     */
    public function show(FileSignature $fileSignature)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FileSignature  $fileSignature
     * @return \Illuminate\Http\Response
     */
    public function edit(FileSignature $fileSignature)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFileSignatureRequest  $request
     * @param  \App\Models\FileSignature  $fileSignature
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFileSignatureRequest $request, FileSignature $fileSignature)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FileSignature  $fileSignature
     * @return \Illuminate\Http\Response
     */
    public function destroy(FileSignature $fileSignature)
    {
        //
    }
}
