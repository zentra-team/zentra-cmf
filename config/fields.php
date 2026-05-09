<?php

return [
    'text'     => \App\Fields\TextField::class,
    'textarea' => \App\Fields\TextareaField::class,
    'wysiwyg'  => \App\Fields\WysiwygField::class,
    'markdown' => \App\Fields\MarkdownField::class,
    'code'     => \App\Fields\CodeField::class,

    'number'        => \App\Fields\NumberField::class,
    'checkbox'      => \App\Fields\CheckboxField::class,
    'tags'          => \App\Fields\TagsField::class,
    'select'        => \App\Fields\SelectField::class,
    'radio'         => \App\Fields\RadioField::class,
    'checkbox_list' => \App\Fields\CheckboxListField::class,
    'rating'        => \App\Fields\RatingField::class,
    'slider'        => \App\Fields\SliderField::class,
    'repeater'      => \App\Fields\RepeaterField::class,
    'price'         => \App\Fields\PriceField::class,
    'keyvalue'      => \App\Fields\KeyValueField::class,

    'date'     => \App\Fields\DateField::class,
    'datetime' => \App\Fields\DateTimeField::class,
    'time'     => \App\Fields\TimeField::class,

    'email' => \App\Fields\EmailField::class,
    'url'   => \App\Fields\UrlField::class,
    'phone' => \App\Fields\PhoneField::class,

    'color' => \App\Fields\ColorField::class,

    'file' => \App\Fields\FileField::class,

    'doc_link'       => \App\Fields\DocLinkField::class,
    'relation_multi' => \App\Fields\RelationMultiField::class,

    'image'   => \App\Fields\ImageField::class,
    'gallery' => \App\Fields\GalleryField::class,
    'youtube' => \App\Fields\YoutubeField::class,
    'icon'    => \App\Fields\IconField::class,
    'video'   => \App\Fields\VideoField::class,
    'map'     => \App\Fields\MapField::class,
];
