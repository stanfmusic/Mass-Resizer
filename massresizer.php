<?php
/**
 * Plugin Name: Mass Resizer
 * Description: Plugin for bulk resizing & converting to WebP images through the media library.
 * Version: 2.9.200
 * Author: Stan Furtovsky Pro
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * Text Domain: massresizer
 * 
 * © 2024 Stan Furtovsky Pro. All rights reserved.
 * Telegram @stanfmusic
 * e-mail furtovsky@gmail.com
 * This plugin is licensed under the GPLv2 or later.
 */

 if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}



$masslog_switch = true; // log switch on/off 
$massresizer_log = []; // Массив для хранения сообщений
// сбор статистики
$mass_stats = array(   
    'crop_count' => 0,
    'webp_count' => 0,
    'changed_count' => 0,
    'completed_count' => 0,
    'delete_count' => 0,
);
// Счетчик событий обработки
function incr_stats($type) {
    global $mass_stats;

    if ($type === 'crop_count') {
        $mass_stats['crop_count']++;
        $mass_stats['completed_count']++;

    } elseif ($type === 'webp_count') {
        $mass_stats['webp_count']++;
        $mass_stats['completed_count']++;

    } elseif ($type === 'changed_count') {
        $mass_stats['changed_count']++;
        $mass_stats['completed_count']++;    
    }
    elseif ($type === 'delete_count') {
        $mass_stats['delete_count']++;
        $mass_stats['completed_count']++;    
    }
    
}

// Load text domains
function massresize_load_textdomain() {
    load_plugin_textdomain('massresizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'massresize_load_textdomain');

// Register settings
function massresize_register_settings() {
    add_option('massresize_webp', false);
    add_option('massresize_crop', false);
    add_option('massresize_deleteolds', false);
    add_option('massresize_changeold', false);
    add_option('massresize_compression_percentage', 80);
    add_option('massresize_max_width', 1024);
    add_option('massresize_max_height', 800);
    register_setting('massresize_options_group', 'massresize_max_width', 'massresize_validate_width');
    register_setting('massresize_options_group', 'massresize_max_height', 'massresize_validate_height');
    register_setting('massresize_options_group', 'massresize_webp', 'massresize_validate_webp');
    register_setting('massresize_options_group', 'massresize_compression_percentage', 'massresize_validate_procents');
    register_setting('massresize_options_group', 'massresize_crop', 'massresize_validate_crop');
    register_setting('massresize_options_group', 'massresize_changeold', 'massresize_validate_changeold');
    register_setting('massresize_options_group', 'massresize_deleteolds', 'massresize_validate_deleteolds');
}
add_action('admin_init', 'massresize_register_settings');

// Validation functions
function massresize_validate_width($input) {
    $input = intval($input);
    return ($input > 0 && $input <= 5000) ? $input : 1024; // Set default value if input is invalid
}

function massresize_validate_height($input) {
    $input = intval($input);
    return ($input > 0 && $input <= 5000) ? $input : 800; // Set default value if input is invalid
}

//covert to webp on/off
function massresize_validate_webp($input) {
    return filter_var($input, FILTER_VALIDATE_BOOLEAN);
}
//crop  on/off
function massresize_validate_crop($input) {
    return filter_var($input, FILTER_VALIDATE_BOOLEAN);
}
//Replace all images with new WebP versions on all pages on/off
function massresize_validate_changeold($input) {
    return filter_var($input, FILTER_VALIDATE_BOOLEAN);
}
// deletion of old images after conversion on/off
function massresize_validate_deleteolds($input) {
    return filter_var($input, FILTER_VALIDATE_BOOLEAN);
}
// setting compression in %
function massresize_validate_procents($input) {
    $input = intval($input);
    return ($input >= 0 && $input <= 100) ? $input : 100; // Set default value if input is invalid
}

// Options page
function massresize_options_page() {

    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $plugin_data = get_plugin_data( __FILE__ ); // Получаем данные о плагине
    $version = $plugin_data['Version']; 
    $author = $plugin_data['Author']; 
    $massresizer_logo_url = plugin_dir_url(__FILE__) . 'assets/massresizer_logo.png';
?>



<div style="padding-top:20px;">
    

<style>
    .mass-container {
        background-color: #D5E7EB; /* Светлее на 30% от #A7CED4 */
        padding: 30px;
        border-radius: 12px;
        max-width: 700px;
        margin: 0 auto;
        box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1); /* Легкая тень для объема */
        font-family: 'Segoe UI', Tahoma, Geneva, sans-serif;
        transition: box-shadow 0.3s ease;
    }
    .mass-container:hover {
        box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.05); /* Очень слабая тень при наведении */
    }

    .mass-title {
        text-align: center;
        font-size: 1.8em;
        color: #333333; /* Темно-серый для заголовков */
        margin-bottom: 20px;
        font-weight: 600;
    }

    .mass-th-title {
        color: #333333; /* Темно-серый для заголовков таблицы */
        padding-right: 10px;
        text-align: right;
        font-weight: bold;
    }

    .mass-resizer-settings input[type="submit"] {
        background: linear-gradient(45deg, #F4A52A, #D03441); /* Градиент кнопки */
        color: #FFF; /* Белый текст */
        border: none;
        padding: 12px 25px;
        border-radius: 6px;
        cursor: pointer;
        transition: transform 0.2s, background-color 0.3s ease;
    }
    .mass-resizer-settings input[type="submit"]:hover {
        transform: scale(1.05);
        background-color: #F4A52A; /* Оранжевый фон при наведении */
    }

    .mass-disabled input,
    .mass-disabled select {
        opacity: 0.4;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .mass-row {
      
    }

    .mass-row:hover {
background: linear-gradient(to right, rgba(249, 249, 249, 0) 0%, rgba(249, 249, 249, 0.3) 100%);

    }

    .switch-container {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    .switch-container input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #CF3541; /* Фон для неактивного состояния */
        transition: 0.4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: #F4A52A; /* Фон для активного состояния переключателя */
    }
    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .conditional-row.mass-disabled {
        transition: opacity 0.5s ease-in-out;
    }
	.mass-alltable { width:100%;
		margin: 0 auto;
	}
</style>







    <div class="mass-container">
        <h2 class="mass-title"><?php esc_html_e('Mass Resizer Settings', 'massresizer'); ?></h2>
        
        <!-- Логотип под заголовком -->
        <img src="<?php echo $massresizer_logo_url; ?>" alt="Mass Resizer Logo" style="display: block; margin: 20px auto; max-width: 100%; height: auto; 
            border-radius: 12px; box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" />
        
        <p><?php printf(__('Version: %s', 'massresizer'), esc_html($version)); ?></p>
        <p><?php printf(__('Author: %s', 'massresizer'), esc_html($author)); ?></p>

        <form method="post" action="options.php" class="mass-resizer-settings">
            <?php settings_fields('massresize_options_group'); ?>
            <table class="mass-alltable">
                <tr valign="top" class="mass-row">
                    <th scope="row" class="mass-th-title"><?php esc_html_e('Enable image cropping', 'massresizer'); ?></th>
                    <td>
                        <label class="switch-container">
                            <input type="checkbox" id="massresize_crop" name="massresize_crop" value="1" <?php checked(get_option('massresize_crop'), true); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <tr valign="top" class="mass-row">
                    <th scope="row" class="mass-th-title"><?php esc_html_e('Maximum image width', 'massresizer'); ?></th>
                    <td><input type="number" name="massresize_max_width" min="50" max="5000" value="<?php echo esc_attr(get_option('massresize_max_width')); ?>" /></td>
                </tr>
                <tr valign="top" class="mass-row">
                    <th scope="row" class="mass-th-title"><?php esc_html_e('Maximum image height', 'massresizer'); ?></th>
                    <td><input type="number" name="massresize_max_height" min="50" max="5000" value="<?php echo esc_attr(get_option('massresize_max_height')); ?>" /></td>
                </tr>
                <tr valign="top" class="mass-row">
                    <th scope="row" class="mass-th-title"><?php esc_html_e('Enable WebP Compression', 'massresizer'); ?></th>
                    <td>
                        <label class="switch-container">
                            <input type="checkbox" id="massresize_webp" name="massresize_webp" value="1" <?php checked(get_option('massresize_webp'), true); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>

                <tr valign="top" class="conditional-row mass-row mass-disabled">
                    <th scope="row" class="mass-th-title"><?php esc_html_e('Compression Percentage', 'massresizer'); ?></th>
                    <td>
                        <input type="number" name="massresize_compression_percentage" min="0" max="100" value="<?php echo esc_attr(get_option('massresize_compression_percentage', 80)); ?>" />
                        <p><?php esc_html_e('Set the compression level for WebP images (0-100%)', 'massresizer'); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="conditional-row mass-row mass-disabled">
                    <th scope="row" class="mass-th-title"><?php esc_html_e('Replace photos on all pages', 'massresizer'); ?></th>
                    <td>
                        <label class="switch-container">
                            <input type="checkbox" id="massresize_changeold" name="massresize_changeold" value="1" <?php checked(get_option('massresize_changeold'), true); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>

                <tr valign="top" class="conditional-row mass-row mass-disabled">
                    <th scope="row" class="mass-th-title"><?php esc_html_e('Remove old images after conversion', 'massresizer'); ?></th>
                    <td>
                        <label class="switch-container">
                            <input type="checkbox" id="massresize_deleteolds" name="massresize_deleteolds" value="1" <?php checked(get_option('massresize_deleteolds'), true); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            </table>

           <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const webpCheckbox = document.getElementById('massresize_webp');
        const cropCheckbox = document.getElementById('massresize_crop');
        const compressionPercentageRow = document.querySelector('input[name="massresize_compression_percentage"]').closest('.conditional-row');
        const changeOldCheckbox = document.getElementById('massresize_changeold').closest('.conditional-row');
        const deleteOldsCheckbox = document.getElementById('massresize_deleteolds').closest('.conditional-row');

        function toggleRows() {
            const isWebpEnabled = webpCheckbox.checked;

            // Показать или скрыть ряды в зависимости от включенного WebP
            compressionPercentageRow.classList.toggle('mass-disabled', !isWebpEnabled);
            changeOldCheckbox.classList.toggle('mass-disabled', !isWebpEnabled);
            deleteOldsCheckbox.classList.toggle('mass-disabled', !isWebpEnabled);

            // Заблокировать переключатели WebP
            document.getElementById('massresize_changeold').disabled = !isWebpEnabled;
            document.getElementById('massresize_deleteolds').disabled = !isWebpEnabled;

            // Убедимся, что при выключении WebP переключатели выглядят корректно (сброшены)
            if (!isWebpEnabled) {
                document.getElementById('massresize_changeold').checked = false;
                document.getElementById('massresize_deleteolds').checked = false;
            }

            // Если включен "Enable image cropping", то блокируем replace photos
            if (cropCheckbox.checked) {
                document.getElementById('massresize_changeold').checked = false;
                document.getElementById('massresize_changeold').disabled = true;
            }
        }

        function toggleChangeOld() {
            const isCropEnabled = cropCheckbox.checked;

            if (isCropEnabled) {
                // Выключаем и блокируем massresize_changeold, если включен massresize_crop
                document.getElementById('massresize_changeold').checked = false;
                document.getElementById('massresize_changeold').disabled = true;
            } else {
                // Включаем и разблокируем massresize_changeold, если massresize_crop выключен
                if (webpCheckbox.checked) {
                    document.getElementById('massresize_changeold').disabled = false;
                }
            }
        }

        // Инициализация
        toggleRows();  // Обновляем видимость рядов в зависимости от WebP
        toggleChangeOld();  // Проверяем состояние переключателя crop

        // Слушатели событий
        webpCheckbox.addEventListener('change', function() {
            toggleRows();  // Обновляем состояние рядов при изменении WebP
        });

        cropCheckbox.addEventListener('change', function() {
            toggleChangeOld();  // Обновляем состояние переключателя changeold при изменении crop
        });

        // Снимаем блокировку перед отправкой формы, чтобы значения попали в запрос
        document.querySelector('form').addEventListener('submit', function() {
            // Снимаем `disabled` перед отправкой формы
            document.getElementById('massresize_changeold').disabled = false;
            document.getElementById('massresize_deleteolds').disabled = false;
            document.getElementById('massresize_webp').disabled = false;
        });
    });
</script>











            <p><?php esc_html_e('Note: It\'s recommended to create a backup before using this feature, as some changes may be irreversible.', 'massresizer');?></p>
            <input type="submit" value="<?php esc_html_e('Save Settings', 'massresizer'); ?>" />
        </form>

        <h2>Support the Plugin</h2>
        <p class="mass-crypto-info"><?php esc_html_e('If you enjoy using this plugin and would like to support its continued development, consider making a donation.', 'massresizer');?></p>
        <ul class="mass-crypto-info">
            <li><strong><?php esc_html_e('Donate with Crypto', 'massresizer');?></strong></li>
            <li>BTC: <strong>1wEPRrCsA7fUza8SjmaeAztgzrwN5Yunu</strong></li>
            <li>USDT(ERC20): <strong>0xc44ee3d0e680eb582b044219f82031b00f3ffed5</strong></li>
            <li>USDT(TRC20): <strong>TR5LTYdgNoPBwn38ECFNt5aUZWffvm1oh6</strong></li>
        </ul>
        <p><strong><?php esc_html_e('EU Bank Account Transfer:', 'massresizer');?></strong></p>
        <ul>
            <li><strong>Name:</strong> Stanislav Furtovskiy</li>
            <li><strong>Account Number:</strong> LT843500010016679999</li>
            <li><strong>SWIFT/BIC:</strong> EVIULT2VXXX</li>
            <li><strong>Telegram:</strong> <a href="https://t.me/stanfmusic">@stanfmusic</a></li>
            <li><strong>E-mail:</strong> <a href="mailto:furtovsky@gmail.com">furtovsky@gmail.com</a></li>
        </ul>
        <p><?php esc_html_e('Thank you for your support!','massresizer');?></p>
    </div>
</div>







<?php
}



// Add admin menu for settings page
function massresize_add_admin_menu() {
    if (current_user_can('manage_options')) {
        add_options_page(
            'Mass Resizer Settings',
            'Mass Resizer',
            'manage_options',
            'massresizer',
            'massresize_options_page'
            
        );
    }    
}
add_action('admin_menu', 'massresize_add_admin_menu');



// Add bulk action to the media library
function massresize_register_custom_bulk_actions($bulk_actions) {
    $bulk_actions['bulk_image_resize'] = __('Mass Resize Images', 'massresizer');
    return $bulk_actions;
}
add_filter('bulk_actions-upload', 'massresize_register_custom_bulk_actions');




// Execute action for selected images
function massresize_handle_custom_bulk_action($redirect_to, $action, $post_ids) {

    global $massresizer_log, $masslog_switch, $mass_stats;
    if ($action !== 'bulk_image_resize') {
        return $redirect_to;
    }


    foreach ($post_ids as $post_id) {

        massresize_crop_by_id($post_id);
    
        // Очищаем объект редактора изображения из памяти
        unset($image_editor);       
    
    }

    // Export full log messages to => error_log
    //if ($massresizer_log && $masslog_switch) {
    //    foreach ($massresizer_log as $log_message) {
     //       error_log($log_message);
     //   }
    //}

    
    // Очищаем кэш WordPress
    wp_cache_flush();



    // Сохраняем сообщения в транзиент
    set_transient('massresize_conversion_messages', $mass_stats, 60); // Храним 60 секунд
    return $redirect_to;
}





// Function to resize an image by ID
function massresize_crop_by_id($image_id) {
    
    $image_path = get_attached_file($image_id);
    
    if (!file_exists($image_path)) {
        massresize_log_messages (sprintf(__('File not found for ID: %d', 'massresizer'), $image_id));
        return;
    }

    list($width, $height) = getimagesize($image_path);

    // Get the maximum size settings
    $max_width = get_option('massresize_max_width', 1024);
    $max_height = get_option('massresize_max_height', 800);
    $webp_percentage = get_option('massresize_compression_percentage', 80);
    $webp_switch = get_option ('massresize_webp', false);
    $crop_switch = get_option ('massresize_crop', false);
    $chenge_switch = get_option ('massresize_changeold', false);
    $delete_switch = get_option ('massresize_deleteolds', false);

    // Check first condition: height is greater than width and greater than the maximum height
    if ($crop_switch && ($height > $width && $height > $max_height)) {
        $new_height = $max_height;
        $new_width = intval(($width / $height) * $new_height); // Proportional width adjustment
    } 
    // Check second condition: width is greater than the maximum width
    elseif ($crop_switch && ($width > $max_width)) {
        $new_width = $max_width;
        $new_height = intval(($height / $width) * $new_width); // Proportional height adjustment
    } 
    else {  // Если не надо кропить, проверяем надо ли конвертировать в формат WebP
        
        if ($webp_switch) {
            // Здесь код для конвертации в формат WebP
            massresize_convert_to_webp ($image_id, $webp_percentage, $image_path, $chenge_switch, $delete_switch);
        }

    massresize_log_messages (sprintf(__('No need to resize photo. Current dimensions: %dx%d', 'massresizer'), $width, $height));
    return;        
    }


    // Load the image and resize it
    $image_editor = wp_get_image_editor($image_path);
    if (is_wp_error($image_editor)) {

        massresize_log_messages (sprintf(__('Error loading image for ID: %d', 'massresizer'), $image_id));
        return;
    }

    $image_editor->resize($new_width, $new_height, true);
    $saved = $image_editor->save($image_path);
    
    if (is_wp_error($saved)) {

        massresize_log_messages (sprintf(__('Error resizing for ID: %d', 'massresizer'), $image_id));
        return;
    }

    // Update metadata
    $metadata = wp_get_attachment_metadata($image_id);
    $metadata['width'] = $new_width;
    $metadata['height'] = $new_height;
    wp_update_attachment_metadata($image_id, $metadata);


    if ($webp_switch) {
        // вызов конвертации в формат WebP
        massresize_convert_to_webp($image_id, $webp_percentage, $image_path, $chenge_switch, $delete_switch);
        }
                 // massresize_log_messages (sprintf(__('Image converted to WebP format. Current dimensions: %dx%d', 'massresizer'), $new_width, $new_height));
    

    massresize_log_messages (sprintf(__('Image dimensions for ID: %d changed to %dx%d', 'massresizer'), $image_id, $new_width, $new_height));   
    incr_stats('crop_count');
    return;   
}




// Function convertion image (JPEG&PNG) to webp
function massresize_convert_to_webp($image_id, $webp_percentage, $image_path, $chenge_switch, $delete_switch, $crop_switch) {


    // Получаем информацию об изображении
    $image_info = getimagesize($image_path);

    // Проверяем, удалось ли получить информацию
    if ($image_info === false) {

        massresize_log_messages (sprintf(__('Failed to get image information for ID: %d', 'massresizer'), $image_id));
        return;
    }

    // Получаем MIME-тип
    $mime_type = $image_info['mime'];

    // Проверяем, что изображение в формате JPEG или PNG
    if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {

        massresize_log_messages (sprintf(__('Error! Invalid image format for ID: %d. Only JPEG and PNG are supported.', 'massresizer'), $image_id));
        return;
    }        

    // Загружаем оригинальное изображение
    $image = wp_get_image_editor($image_path);
    if (is_wp_error($image)) {

        massresize_log_messages (sprintf(__('Error loading original image for ID: %d', 'massresizer'), $image_id));
        return;
    }


    // Получаем путь для файла WebP
    $dirname = pathinfo($image_path, PATHINFO_DIRNAME);
    $filename = pathinfo($image_path, PATHINFO_FILENAME);
    $webp_path = "{$dirname}/{$filename}.webp";


    // Проверка существования WebP по метаданным
    global $wpdb;
    $webpfile_name = basename($webp_path); // Извлекаем имя файла, например

    // Формируем SQL-запрос с подстановкой для поиска
    $sql_query = $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_wp_attached_file' 
        AND meta_value LIKE %s",
        '%' . $wpdb->esc_like($webpfile_name)
    );

        // Выводим отладочную информацию о запросе
       // error_log("SQL Query: " . $sql_query);

    // Выполняем запрос и сохраняем результат
    $existing_webp_id = $wpdb->get_var($sql_query);

    // Проверка результата и вывод в лог
    if ($existing_webp_id) {
        wp_delete_attachment($existing_webp_id, true);

        massresize_log_messages (sprintf(__('The old converted file %d was deleted to avoid duplication.','massresizer'), $webpfile_name));
        // if already exists in WordPress
    }


    $image->set_quality($webp_percentage); // Установка качества
    $saved = $image->save($webp_path, 'image/webp'); // Сохранение как WebP

    if (is_wp_error($saved)) {

        massresize_log_messages (sprintf(__('Error saving WebP for ID: %d', 'massresizer'), $image_id));
        return;
    }

    // Добавление нового изображения в медиабиблиотеку
    $attachment = [
        'guid'           => $webp_path,
        'post_mime_type' => 'image/webp',
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ];

    // Вставляем в базу данных
    $new_image_id = wp_insert_attachment($attachment, $webp_path);

    // Обновляем метаданные
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($new_image_id, $webp_path);
    wp_update_attachment_metadata($new_image_id, $attach_data);


    // Получаем метаданные WebP-изображения
    $massresize_metadata = wp_get_attachment_metadata($new_image_id);

    // Добавляем ID оригинального изображения в массив метаданных
    $massresize_metadata['original_image_id'] = $image_id;

    // Обновляем метаданные WebP-изображения
    wp_update_attachment_metadata($new_image_id, $massresize_metadata);


    // вызов функции замены во всех постах фото на фото в WebP формате
    if ($chenge_switch && !$crop_switch) {
        replace_images_with_webp($image_id);
    }
    
    // вызов функции удаления старой фотографии
    if ($delete_switch && file_exists($image_path)) {
        massresize_log_messages (sprintf(__('Image path: %d', 'massresizer'), $image_path));
        wp_delete_attachment($image_id, true); //  удаляем оригинал  из базы и с диска
        incr_stats('delete_count');
        massresize_log_messages (sprintf(__('Image with ID: %d has been deleted', 'massresizer'), $image_id));
    }
   
    massresize_log_messages (sprintf(__('Converted image for ID: %d to WebP format with ID: %d', 'massresizer'), $image_id, $new_image_id));
    incr_stats('webp_count');
    return;
}


    



// Функция замены фотографии на фотографию в WebP формате
function replace_images_with_webp($image_id) {
    global $wpdb;

    // Логируем начало замены для указанного image_id
    massresize_log_messages(sprintf(__('Start replacing images for ID: %d', 'massresizer'), $image_id));

    // Получаем WebP ID по оригинальному $image_id
    $webp_image_id = $wpdb->get_var(
        $wpdb->prepare("
            SELECT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_wp_attachment_metadata'
            AND meta_value LIKE %s
            LIMIT 1
        ", '%"original_image_id";i:' . $image_id . ';%')
    );

    // Если WebP ID не найден, логируем ошибку и прерываем выполнение
    if (!$webp_image_id) {
        massresize_log_messages(sprintf(__('Error: No WebP image found for ID: %d', 'massresizer'), $image_id));
        return;
    }

    // Получаем URL нового изображения и приводим к относительному
    $mass_new_urls['original'] = wp_get_attachment_url($webp_image_id);

    // Получаем метаданные изображения и добавляем относительные URL миниатюр
    $thumbnail_sizes = wp_get_attachment_metadata($webp_image_id);
    if ($thumbnail_sizes && isset($thumbnail_sizes['sizes'])) {
        foreach ($thumbnail_sizes['sizes'] as $size => $info) {
            $url = wp_get_attachment_image_url($webp_image_id, $size);
            if ($url) {
                $mass_new_urls[$size] = $url;
            } else {
                massresize_log_messages(sprintf(__('Error: Could not get URL for WebP image size: %s for ID: %d', 'massresizer'), $size, $webp_image_id));
            }
        }
    } else {
        massresize_log_messages(sprintf(__('Error: No thumbnail sizes found for WebP image ID: %d', 'massresizer'), $webp_image_id));
    }

    // Получаем URL оригинала старого изображения
    $mass_old_urls['original'] = wp_get_attachment_url($image_id);

    // Получаем метаданные изображения
    $thumbnail_sizes = wp_get_attachment_metadata($image_id);
    if ($thumbnail_sizes && isset($thumbnail_sizes['sizes'])) {
        foreach ($thumbnail_sizes['sizes'] as $size => $info) {
            // Получаем полный URL для текущего размера
            $url = wp_get_attachment_image_url($image_id, $size);
            if ($url) {
                $mass_old_urls[$size] = $url;
            } else {
                massresize_log_messages(sprintf(__('Error: Could not get URL for original image size: %s for ID: %d', 'massresizer'), $size, $image_id));
            }
        }
    } else {
        massresize_log_messages(sprintf(__('Error: No thumbnail sizes found for original image ID: %d', 'massresizer'), $image_id));
    }

    $page = 1; // Начинаем с первой страницы
    $posts_per_page = 100; // Обрабатываем по 100 постов за раз

    while (true) {
        // Получаем 100 постов за раз
        $args = [
            'post_type' => ['post', 'page'],
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
        ];

        $posts = get_posts($args);

        // Если постов нет, выходим из цикла
        if (empty($posts)) {
            break;
        }

        foreach ($posts as $post) {
            $post_content = $post->post_content;

            // Проходим по каждому старому URL
            foreach ($mass_old_urls as $size => $old_url) {
                // Если старый URL найден в контенте, заменяем его на новый
                if (strpos($post_content, $old_url) !== false && isset($mass_new_urls[$size])) {
                    $new_url = $mass_new_urls[$size];
                    // Заменяем старый URL на новый в контенте
                    $post_content = str_replace($old_url, $new_url, $post_content);
                }
            }

            // Обновляем пост с новым контентом
            wp_update_post($post);
        }

        // Переходим к следующей странице
        $page++;
    }

    // Логируем успешное завершение замены изображений
    massresize_log_messages(sprintf(__('Successfully replaced images for ID: %d', 'massresizer'), $image_id));
    incr_stats('changed_count');
}

















// Вывод сообщения на странице админки
function massresize_log_admin_notice() {
    global $mass_stats;
    // Получаем данные из транзиента
    $mass_stats = get_transient('massresize_conversion_messages');

    // Если нет завершенных обработок, не показывать уведомление
    if (empty($mass_stats['completed_count'])) {
        return;
    }

    // Формируем сообщение с использованием локализации
    $message = sprintf(__('Image processing is complete: %d images cropped, %d images converted to WebP', 'massresizer'),
        $mass_stats['crop_count'] ?? 0,
        $mass_stats['webp_count'] ?? 0) . '<br />' . sprintf(__('Photos replaced on pages: %d', 'massresizer'),
        $mass_stats['changed_count'] ?? 0) . '<br />' . sprintf(__('Number of deleted original photos: %d', 'massresizer'),
        $mass_stats['delete_count'] ?? 0) . '<br />' . sprintf(__('Total number of completed processes: %d', 'massresizer'),
        $mass_stats['completed_count'] ?? 0);

    // Выводим уведомление
    echo '<div class="notice notice-success is-dismissible">
             <p>' . $message . '</p>
             </div>';
// Удаляем транзиент после отображения сообщений
delete_transient('massresize_conversion_messages');
}


        



// Функция для сбора всех логов от плагина DEBUG_log функция
function massresize_log_messages($masslog) {
    global $masslog_switch, $massresizer_log;
    
    if ($masslog_switch){
        $massresizer_log [] = $masslog;
    }
}



// Подключаем функцию для вывода уведомлений
add_action('admin_notices', 'massresize_log_admin_notice');

// Подключаем обработчик массовых действий
add_filter('handle_bulk_actions-upload', 'massresize_handle_custom_bulk_action', 10, 3);
