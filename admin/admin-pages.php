<?php

add_action('init', 'register_lead_post_type');
function register_lead_post_type() {
    register_post_type('lead', [
        'public' => false, 
        'label'  => 'Leads',
        'show_ui' => true, 
        'supports' => ['title', 'editor'] 
    ]);
}
