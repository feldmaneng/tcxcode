<?php namespace Config;

use CodeIgniter\Config\BaseConfig;

class GroceryCrud extends BaseConfig
{
    public function getDefaultConfig() {
        helper('url');

        return [
            // So far 34 languages including: Afrikaans, Arabic, Bengali, Bulgarian, Catalan, Chinese, Czech, Danish,
            // Dutch, English, French, German, Greek, Hindi, Hungarian, Indonesian, Italian, Japanese, Korean,
            // Lithuanian, Mongolian, Norwegian, Persian, Polish, Portuguese, Brazilian Portuguese, Romanian,
            // Russian, Slovak, Spanish, Thai, Turkish, Ukrainian, Vietnamese
            'default_language'	=> 'English',

            // This is the 'assets' folder where all the JavaScript, CSS, images and font files are located
           //'assets_folder' => base_url() . '/vendor/grocery-crud/',
			'assets_folder' => base_url() . 'vendor/grocery-crud/enterprise/public/vendor/grocery-crud/',

            // The default per page when a user firstly see a list page
            'default_per_page'	=> '10',

            // Having some options at the list paging. This is the default one that all the websites are using.
            // Make sure that the number of grocery_crud_default_per_page variable is included to this array.
            'paging_options' => ['10', '25', '50', '100'],

            // The environment is important, so we can have specific configurations for specific environments
            'environment' => 'development',

            // The default theme that Grocery CRUD will use. Currently, you can choose between 'bootstrap-v3' and 'bootstrap-v4'
            'theme' => 'bootstrap-v5',

            'xss_clean' => false,

            // The character limiter at the datagrid columns, zero(0) value if you don't want any character
            // limitation to the column
            'column_character_limiter' => 50,

            // The allowed file types on upload. If the file extension doesn't exist in the array
            // it will throw an error and the upload will not be completed
            'upload_allowed_file_types' =>  [
                'gif', 'jpeg', 'jpg', 'png', 'svg', 'tiff', 'doc', 'docx',  'rtf', 'txt', 'odt', 'xls', 'xlsx', 'pdf',
                'ppt', 'pptx', 'pps', 'ppsx', 'mp3', 'm4a', 'ogg', 'wav', 'mp4', 'm4v', 'mov', 'wmv', 'flv', 'avi',
                'mpg', 'ogv', '3gp', '3g2', 'psd'
            ],

            'remove_file_on_delete' => false,

            // Show image preview - As we currently don't have thumbnails to show please keep in mind that the full
            // image will be loaded in the browser.
            'show_image_preview' => true,

            'open_in_modal' => true,

            'url_history' => true,

            // Configuration to publish action events (coming from Redux) with all the details that the action is having
            // This is useful to have a better control over the events that are being triggered in the application
            // However, please use with caution as this may leak some sensitive information to the application
            // For more read about security issues about XSS see wikipedia link
            // here https://en.wikipedia.org/wiki/Cross-site_scripting
            'publish_events' => true,

            // The button style that we have for the action buttons at the datagrid (list) page
            // Choose between 'icon', 'text', 'icon-text'
            'action_button_type' => 'icon-text',

            'max_action_buttons' => [
                'mobile' => 1,
                'desktop' => 2
            ],

            // Choose between 'left' or 'right'
            'actions_column_side' => 'left',

            // We have noticed that especially after using setRelation within a table that had more than 10K rows
            // that the datagrid was getting slower. For that reason we have optimized SQL wherever possible, and we
            // also have disabled the ordering for setRelation fields.  Keep in mind that the optimization of the queries
            // can be up to 20x faster!! Especially in big tables (e.g. with 1 million rows).
            // In case you would like though to use the ordering for the setRelation field, and you don't have big tables
            // you can set this to `false` and you will probably not notice any difference
            'optimize_sql_queries' => false,

            // Remember the quick search upon refresh. The search information is stored in the browser local storage
            'remember_quick_search' => true,
        ];
    }
}
