<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VolunteerStory extends Model {
    
    /**
     * Table name.
     * @var string
     */
    protected $table = 'volunteers_stories';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'other_information',
        'description',
        'image',
        'status',
        'meta_title',
        'meta_description',
        'created_by',
        'updated_by'

    ];
    
    /**
     * The users that belong to the category.
     */
    public function createdBy() {
        return $this->belongsTo('App\User', 'created_by');
    }

    /**
     * The users that belong to the category.
     */
    public function updatedBy() {
        return $this->belongsTo('App\User', 'updated_by');
    }

}
