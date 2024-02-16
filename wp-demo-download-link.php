<?php
/**
 * Plugin Name: WPDD link
 * Plugin URI: https://www.jingxialai.com/4462.html
 * Description: 支持添加演示地址和下载协议的简单资源下载辅助插件。
 * Version: 1.0
 * Author: Summer
 */

// 添加到文章编辑下面
function wp_demo_download_meta_box() {
    add_meta_box(
        'wp_demo_download_meta_box',
        '演示和下载地址(先到外观-小工具-添加此工具到文章边栏，地址任选一种方式即可)',
        'wp_demo_download_meta_box_content',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'wp_demo_download_meta_box');

// 设置
function wp_demo_download_meta_box_content($post) {
    // 写入链接
    $demo_url = get_post_meta($post->ID, '_demo_url', true);
    $download_url = get_post_meta($post->ID, '_download_url', true);
    $additional_download_url = get_post_meta($post->ID, '_additional_download_url', true);

    wp_nonce_field('wp_demo_download_meta_box_nonce', 'demo_download_nonce');

    // 设置链接
    echo '<label for="demo_url">演示地址:</label>';
    echo '<input type="text" id="demo_url" name="demo_url" value="' . esc_attr($demo_url) . '" style="width:100%;" /><br />';

    echo '<label for="download_url">下载地址(需要用户同意下载协议):</label>';
    echo '<input type="text" id="download_url" name="download_url" value="' . esc_attr($download_url) . '" style="width:100%;" /><br />';

    echo '<label for="additional_download_url">下载地址(直接下载):</label>';
    echo '<input type="text" id="additional_download_url" name="additional_download_url" value="' . esc_attr($additional_download_url) . '" style="width:100%;" />';
}

// 保存
function demo_download_save_post_data($post_id) {

    if (!isset($_POST['demo_download_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['demo_download_nonce'], 'wp_demo_download_meta_box_nonce')) {
        return;
    }

    // 判断是否保存
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // 检查权限
    if ('post' === $_POST['post_type'] && !current_user_can('edit_post', $post_id)) {
        return;
    }


    if (isset($_POST['demo_url'])) {
        update_post_meta($post_id, '_demo_url', esc_url($_POST['demo_url']));
    }

    if (isset($_POST['download_url'])) {
        update_post_meta($post_id, '_download_url', esc_url($_POST['download_url']));
    }

    if (isset($_POST['additional_download_url'])) {
        update_post_meta($post_id, '_additional_download_url', esc_url($_POST['additional_download_url']));
    }
}
add_action('save_post', 'demo_download_save_post_data');

// 添加到小工具
class Demo_Download_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'demo_download_widget',
            '演示地址和下载协议设置',
            array('description' => 'WPDD link插件 先添加到正文的边栏中，协议支持HTML文本')
        );
    }

    function widget($args, $instance) {
        if (is_single()) {
            $demo_url = get_post_meta(get_the_ID(), '_demo_url', true);
            $download_url = get_post_meta(get_the_ID(), '_download_url', true);
            $additional_download_url = get_post_meta(get_the_ID(), '_additional_download_url', true);

            if ($demo_url || $download_url || $additional_download_url) {
                echo $args['before_widget'];

                $title = apply_filters('widget_title', $instance['title']);
                $download_agreement_text = isset($instance['download_agreement_text']) ? $instance['download_agreement_text'] : '';

                if (!empty($title)) {
                    echo $args['before_title'] . $title . $args['after_title'];
                }

                echo '<div class="demo-download-sidebar">';

                if ($demo_url) {
                    echo '<p><strong>演示:</strong> <a href="' . esc_url($demo_url) . '" class="button view-demo" target="_blank">查看演示</a></p>';
                }

                if ($download_url) {
                    // 前端下载按钮 需要同意协议
                    echo '<p><strong>下载地址:</strong> <a href="#" class="button download-now" id="download-now-btn" data-agreement-text="' . esc_attr($download_agreement_text) . '" data-download-url="' . esc_url($download_url) . '" target="_blank">点击下载</a></p>';
                }

                if ($additional_download_url) {
                    // 直接下载
                    echo '<p><strong>下载地址:</strong> <a href="' . esc_url($additional_download_url) . '" class="button download-now" target="_blank">直接下载</a></p>';
                }

                // 协议框
                echo '<div id="download-modal" style="display: none;">
                    <div id="download-modal-content">
                        <div id="download-agreement-text"></div>
                        <button id="agree-btn">我已阅读并同意此协议</button>
                    </div>
                </div>
                <script>
    document.addEventListener("DOMContentLoaded", function() {
        var downloadButton = document.getElementById("download-now-btn");
        var downloadModal = document.getElementById("download-modal");
        var agreeButton = document.getElementById("agree-btn");
        var agreementText = document.getElementById("download-agreement-text");

        if (downloadButton && downloadModal && agreeButton && agreementText) {
            downloadButton.addEventListener("click", function(e) {
                e.preventDefault();
                agreementText.innerHTML = downloadButton.getAttribute("data-agreement-text");
                downloadModal.style.display = "block";
            });

            agreeButton.addEventListener("click", function() {
                downloadModal.style.display = "none";
                window.open(downloadButton.getAttribute("data-download-url"), "_blank");
            });
        }
    });

                </script>';

                echo '</div>';

                echo $args['after_widget'];
            }
        }
    }

    function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $download_agreement_text = isset($instance['download_agreement_text']) ? $instance['download_agreement_text'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">标题:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('download_agreement_text'); ?>">下载协议 (支持HTML文本):</label>
            <textarea class="widefat" id="<?php echo $this->get_field_id('download_agreement_text'); ?>" name="<?php echo $this->get_field_name('download_agreement_text'); ?>"><?php echo esc_textarea($download_agreement_text); ?></textarea>
        </p>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['download_agreement_text'] = isset($new_instance['download_agreement_text']) ? $new_instance['download_agreement_text'] : '';
        return $instance;
    }
}

function register_demo_download_widget() {
    register_widget('Demo_Download_Widget');
}
add_action('widgets_init', 'register_demo_download_widget');

// css代码
function wp_add_demo_download_styles() {
    echo '<style>
        .demo-download-sidebar a.button.view-demo {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2ecc71;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .demo-download-sidebar a.button.view-demo:hover {
            background-color: #27ae60;
        }

        .demo-download-sidebar a.button.download-now {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0d6efd;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .demo-download-sidebar a.button.download-now:hover {
            background-color: #2980b9;
        }

        #download-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #ff2e2e;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 9999;
        }

        #download-modal-content {
            padding: 20px;
            text-align: center;
        }

        #download-modal-content div {
            margin-bottom: 20px;
        }

        #download-modal-content button {
            padding: 10px 20px;
            background-color: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        #download-modal-content button:hover {
            background-color: #2980b9;
        }
    </style>';
}
add_action('wp_head', 'wp_add_demo_download_styles');
