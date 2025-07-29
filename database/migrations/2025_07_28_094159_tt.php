<?php

use App\Models\Companies;
use App\Models\Workers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("workers", function (Blueprint $table) {
            $table->unsignedBigInteger("company_id");
            // $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
        });

        Workers::distinct()->get('company')->each(function($company) {
            $id = Companies::insertGetId([
                'title' => $company->company
            ]);
            DB::raw("update workers set company_id=$id where company LIKE %$company->company%;");
        });

        Schema::table("workers", function (Blueprint $table) {
            $table->dropColumn(["company", "break_started_at", "break_ended_at"]);
        });

        Schema::table("logs", function (Blueprint $table) {
            $table->dropColumn(["updated_at", "created_at", "type"]);
            $table->dateTime("started_at");
            $table->dateTime("ended_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
