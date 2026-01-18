<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for PDF generation using dompdf.
    |
    */

    'default_paper_size' => 'a4',

    'default_orientation' => 'portrait',

    'defines' => [
        /**
         * The DPI setting
         */
        "DOMPDF_DPI" => 96,

        /**
         * Enable inline PHP
         */
        "DOMPDF_ENABLE_PHP" => false,

        /**
         * Enable inline Javascript
         */
        "DOMPDF_ENABLE_JAVASCRIPT" => true,

        /**
         * Enable remote file access
         */
        "DOMPDF_ENABLE_REMOTE" => true,

        /**
         * A ratio applied to the fonts height to be more like browsers' line height
         */
        "DOMPDF_FONT_HEIGHT_RATIO" => 1.1,

        /**
         * Use the more-than-experimental HTML5 Lib parser
         */
        "DOMPDF_ENABLE_HTML5PARSER" => false,
    ],

];