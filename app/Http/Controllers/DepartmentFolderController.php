<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentFolderRequest;
use App\Http\Requests\UpdateDepartmentFolderRequest;
use App\Models\DepartmentFolder;

class DepartmentFolderController extends Controller
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
     * @param  \App\Http\Requests\StoreDepartmentFolderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDepartmentFolderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DepartmentFolder  $departmentFolder
     * @return \Illuminate\Http\Response
     */
    public function show(DepartmentFolder $departmentFolder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DepartmentFolder  $departmentFolder
     * @return \Illuminate\Http\Response
     */
    public function edit(DepartmentFolder $departmentFolder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDepartmentFolderRequest  $request
     * @param  \App\Models\DepartmentFolder  $departmentFolder
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDepartmentFolderRequest $request, DepartmentFolder $departmentFolder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DepartmentFolder  $departmentFolder
     * @return \Illuminate\Http\Response
     */
    public function destroy(DepartmentFolder $departmentFolder)
    {
        //
    }
}
