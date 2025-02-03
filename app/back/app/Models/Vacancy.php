<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Law
 *
 * @property int                             $id                   Уникальный идентификатор закона
 * @property string                          $link                 Ссылка на вакансию
 * @property string                          $title                Название вакансии
 * @property string                          $description          Описание вакансии
 * @property string                          $salary               Зарплата
 * @property string                          $location             Местоположение
 * @property \Illuminate\Support\Carbon|null $publication_time     Время публикации
 * @property \Illuminate\Support\Carbon|null $created_at           Время создания записи
 * @property \Illuminate\Support\Carbon|null $updated_at           Время последнего обновления записи
 */
class Vacancy extends Model
{
    protected $table = 'vacancies';

    protected $fillable = [
        'link',
        'title',
        'description',
        'salary',
        'location',
        'publication_time',
        'created_at',
        'updated_at',
    ];
}
