<?php

// database/migrations/xxxx_create_patients_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn')->unique(); // Medical Record Number
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->text('primary_diagnosis');
            $table->json('medical_history')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}

// database/migrations/xxxx_create_cases_table.php
class CreateCasesTable extends Migration
{
    public function up()
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['draft', 'active', 'review', 'closed']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->timestamp('next_review_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}

// database/migrations/xxxx_create_case_discussions_table.php
class CreateCaseDiscussionsTable extends Migration
{
    public function up()
    {
        Schema::create('case_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->text('content');
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}

// database/migrations/xxxx_create_teams_table.php
class CreateTeamsTable extends Migration
{
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('specialty');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}

// database/migrations/xxxx_create_team_user_table.php
class CreateTeamUserTable extends Migration
{
    public function up()
    {
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('role', ['lead', 'member', 'consultant']);
            $table->timestamps();
        });
    }
}

// database/migrations/xxxx_create_video_sessions_table.php
class CreateVideoSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('video_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained();
            $table->string('session_id'); // External video provider session ID
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled']);
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}

// app/Models/Patient.php
class Patient extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'mrn',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'primary_diagnosis',
        'medical_history'
    ];

    protected $casts = [
        'medical_history' => 'array',
        'date_of_birth' => 'date'
    ];

    public function cases()
    {
        return $this->hasMany(Case::class);
    }
}

// Additional model relationships will be defined similarly...