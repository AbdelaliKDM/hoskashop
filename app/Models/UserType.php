<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserType extends Model
{
  use HasFactory,SoftDeletes;

  protected $table = 'user_types';

  protected $fillable = [
    'name_ar',
    'name_en',
    'description_ar',
    'description_en'
  ];


  public function name($lang = 'ar')
  {
    return match ($lang) {
      'ar' => $this->name_ar,
      'en' => $this->name_en,
      default => $this->name_ar,
    };
  }

  public function prices()
  {
    return $this->hasMany(Price::class, 'user_type_id', 'id');
  }



}
