<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardMetadataToDocumentTypesTable extends Migration
{
    public function up()
    {
        Schema::table('document_types', function (Blueprint $table) {

            $table->string('icon')
                ->nullable()
                ->after('relation_name');

            $table->string('color')
                ->default('blue')
                ->after('icon');

            $table->integer('dashboard_order')
                ->default(0)
                ->after('color');

            $table->boolean('show_in_dashboard')
                ->default(true)
                ->after('dashboard_order');

            $table->string('dashboard_title')
                ->nullable()
                ->after('show_in_dashboard');

            $table->string('dashboard_subtitle')
                ->nullable()
                ->after('dashboard_title');

            $table->string('view_route')
                ->nullable()
                ->after('dashboard_subtitle');

            $table->string('create_route')
            ->nullable()
            ->after('view_route');
        });
    }

    public function down()
    {
        Schema::table('document_types', function (Blueprint $table) {

            $table->dropColumn([
                'icon',
                'color',
                'dashboard_order',
                'show_in_dashboard',
                'dashboard_title',
                'dashboard_subtitle',
                'view_route',
                'create_route'
            ]);
        });
    }
}