<?php

namespace App\Http\Requests\Admin;

use App\Support\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return $user?->hasAnyPermission(Permission::uploadAllowingPermissions()) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file', 'max:51200',
                'mimes:pdf,doc,docx,odt,rtf,txt,md,csv,xls,xlsx,ods,ppt,pptx,odp,'
                . 'zip,rar,7z,tar,gz,'
                . 'jpg,jpeg,png,gif,webp,svg,bmp,'
                . 'mp3,wav,ogg,flac,m4a,aac,'
                . 'mp4,mov,avi,mkv,webm,'
                . 'json,xml,yaml,yml',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Выберите файл для загрузки.',
            'file.file'     => 'Неверный формат загрузки.',
            'file.mimes'    => 'Этот тип файла запрещён для загрузки.',
            'file.max'      => 'Максимальный размер файла - 50 МБ.',
        ];
    }
}
