<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseLimitRequest;
use App\Http\Requests\UpdateExpenseLimitRequest;
use App\Models\ExpenseLimit;

class ExpenseLimitController extends Controller
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
     * @param  \App\Http\Requests\StoreExpenseLimitRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreExpenseLimitRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ExpenseLimit  $expenseLimit
     * @return \Illuminate\Http\Response
     */
    public function show(ExpenseLimit $expenseLimit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ExpenseLimit  $expenseLimit
     * @return \Illuminate\Http\Response
     */
    public function edit(ExpenseLimit $expenseLimit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateExpenseLimitRequest  $request
     * @param  \App\Models\ExpenseLimit  $expenseLimit
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateExpenseLimitRequest $request, ExpenseLimit $expenseLimit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExpenseLimit  $expenseLimit
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExpenseLimit $expenseLimit)
    {
        //
    }
}
