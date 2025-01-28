<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Law
 *
 * @property int                             $id             Уникальный идентификатор закона
 * @property string                          $link           Ссылка на закон
 * @property string                          $name           Название закона
 * @property int|null                        $size           Размер файла в байтах
 * @property string|null                     $slug           Короткое имя файла из парламента
 * @property bool                            $is_downloaded  Был ли закон скачан с сайта
 * @property bool                            $is_translated  Был ли закон переведен
 * @property \Illuminate\Support\Carbon|null $created_at     Время создания записи
 * @property \Illuminate\Support\Carbon|null $updated_at     Время последнего обновления записи
 */
class Law extends Model
{
    protected $table = 'laws';

    protected $fillable = [
        'link',
        'name',
        'size',
        'slug',
        'is_downloaded',
        'is_translated',
    ];
}
