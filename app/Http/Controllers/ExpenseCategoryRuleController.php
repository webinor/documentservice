<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRuleRequest;
use App\Http\Requests\UpdateExpenseCategoryRuleRequest;
use App\Models\ExpenseCategoryRule;

class ExpenseCategoryRuleController extends Controller
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
     * @param  \App\Http\Requests\StoreExpenseCategoryRuleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreExpenseCategoryRuleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ExpenseCategoryRule  $expenseCategoryRule
     * @return \Illuminate\Http\Response
     */
    public function show(ExpenseCategoryRule $expenseCategoryRule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ExpenseCategoryRule  $expenseCategoryRule
     * @return \Illuminate\Http\Response
     */
    public function edit(ExpenseCategoryRule $expenseCategoryRule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateExpenseCategoryRuleRequest  $request
     * @param  \App\Models\ExpenseCategoryRule  $expenseCategoryRule
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateExpenseCategoryRuleRequest $request, ExpenseCategoryRule $expenseCategoryRule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExpenseCategoryRule  $expenseCategoryRule
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExpenseCategoryRule $expenseCategoryRule)
    {
        //
    }
}
