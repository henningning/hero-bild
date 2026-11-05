<?php
/**
 * Plugin Name: Hero-Bild
 * Description: Individuelle Hero-Bilder für Beiträge, Seiten und Kategorien; responsiver Gutenberg-Block und optionale Verwendung in Abfrage-Loops.
 * Version: 1.4.0
 * Author: Henning Rolapp / OpenAI
 * License: GPL-2.0-or-later
 * Text Domain: hero-bild
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class HR_Hero_Bild {
    const META = '_hr_hero_image_id';
    const TERM_META = '_hr_hero_term_image_id';
    const SETTING = 'hr_hero_loop_source';
    const GLOBAL_SETTING = 'hr_hero_global_settings';
    const DEFAULT_IMAGE = 'hr_hero_default_image_id';
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_block' ) );
        add_action( 'init', array( __CLASS__, 'register_meta' ), 20 );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ) );
        add_action( 'save_post', array( __CLASS__, 'save_box' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
        add_action( 'admin_menu', array( __CLASS__, 'settings_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_filter( 'post_thumbnail_id', array( __CLASS__, 'thumbnail_fallback' ), 10, 2 );
    }
    public static function supported_types() {
        $types = get_post_types( array( 'public' => true ), 'objects' );
        $result = array();
        foreach ( $types as $type ) {
            if ( 'attachment' !== $type->name && post_type_supports( $type->name, 'editor' ) && ! empty( $type->show_ui ) ) {
                $result[] = $type->name;
            }
        }
        return apply_filters( 'hr_hero_supported_post_types', $result );
    }
    public static function supported_taxonomies() {
        $taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
        $result = array();
        foreach ( $taxonomies as $taxonomy ) {
            if ( ! empty( $taxonomy->show_ui ) ) { $result[] = $taxonomy->name; }
        }
        return apply_filters( 'hr_hero_supported_taxonomies', $result );
    }
    public static function register_meta() {
        foreach ( self::supported_types() as $post_type ) {
            register_post_meta( $post_type, self::META, array(
                'single' => true, 'type' => 'integer', 'default' => 0,
                'show_in_rest' => true,
                'sanitize_callback' => 'absint',
                'auth_callback' => function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
            ) );
        }
        // Bilder für Kategorien, Schlagwörter und öffentliche eigene Taxonomien.
        foreach ( self::supported_taxonomies() as $taxonomy ) {
            register_term_meta( $taxonomy, self::TERM_META, array(
                'single' => true, 'type' => 'integer', 'default' => 0,
                'show_in_rest' => true,
                'sanitize_callback' => 'absint',
                'auth_callback' => function ( $allowed, $meta_key, $term_id ) {
                    return current_user_can( 'edit_term', $term_id );
                },
            ) );
            add_action( $taxonomy . '_add_form_fields', array( __CLASS__, 'term_add_field' ) );
            add_action( $taxonomy . '_edit_form_fields', array( __CLASS__, 'term_edit_field' ) );
            add_action( 'created_' . $taxonomy, array( __CLASS__, 'save_term_field' ) );
            add_action( 'edited_' . $taxonomy, array( __CLASS__, 'save_term_field' ) );
        }
    }
    public static function add_boxes() {
        foreach ( self::supported_types() as $post_type ) {
            add_meta_box( 'hr-hero-image', __( 'Hero-Bild', 'hero-bild' ), array( __CLASS__, 'box' ), $post_type, 'side', 'default' );
        }
    }
    public static function box( $post ) {
        wp_nonce_field( 'hr_hero_save', 'hr_hero_nonce' );
        $id = absint( get_post_meta( $post->ID, self::META, true ) );
        $image = $id ? wp_get_attachment_image( $id, 'medium', false, array( 'style' => 'max-width:100%;height:auto;display:block;margin-bottom:8px;' ) ) : '';
        echo '<div id="hr-hero-meta-preview">' . wp_kses_post( $image ) . '</div>';
        echo '<input type="hidden" id="hr-hero-image-id" name="hr_hero_image_id" value="' . esc_attr( $id ) . '">';
        echo '<p><button type="button" class="button" id="hr-hero-choose">' . esc_html__( 'Hero-Bild auswählen', 'hero-bild' ) . '</button> ';
        echo '<button type="button" class="button" id="hr-hero-remove"' . ( $id ? '' : ' style="display:none"' ) . '>' . esc_html__( 'Entfernen', 'hero-bild' ) . '</button></p>';
        echo '<p class="description">' . esc_html__( 'Unabhängig vom Beitragsbild. Ohne Auswahl verwendet der Hero-Block das Beitragsbild. Fehlen beide Bilder, verwendet er das globale Symbolbild.', 'hero-bild' ) . '</p>';
    }
    public static function admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) { return; }
        if ( 'toplevel_page_hr-hero-settings' === $hook ) {
            wp_enqueue_media();
            wp_enqueue_script( 'hr-hero-default', plugins_url( 'assets/default-media.js', __FILE__ ), array( 'jquery' ), '1.4.0', true );
        }
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && in_array( $screen->post_type, self::supported_types(), true ) ) {
            wp_enqueue_media();
            wp_enqueue_script( 'hr-hero-meta', plugins_url( 'assets/meta.js', __FILE__ ), array( 'jquery' ), '1.4.0', true );
        }
        if ( in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) && in_array( $screen->taxonomy, self::supported_taxonomies(), true ) ) {
            wp_enqueue_media();
            wp_enqueue_script( 'hr-hero-term', plugins_url( 'assets/term-media.js', __FILE__ ), array( 'jquery' ), '1.4.0', true );
        }
    }
    public static function save_box( $post_id ) {
        if ( ! isset( $_POST['hr_hero_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hr_hero_nonce'] ) ), 'hr_hero_save' ) ) { return; }
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
        if ( ! isset( $_POST['hr_hero_image_id'] ) ) { return; }
        $id = absint( wp_unslash( $_POST['hr_hero_image_id'] ) );
        if ( $id && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) ) {
            update_post_meta( $post_id, self::META, $id );
        } else {
            delete_post_meta( $post_id, self::META );
        }
    }
    public static function register_block() {
        wp_register_script( 'hr-hero-editor', plugins_url( 'assets/editor.js', __FILE__ ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ), '1.4.0', true );
        wp_add_inline_script( 'hr-hero-editor', 'window.hrHeroEditorData = ' . wp_json_encode( array( 'global' => self::global_settings(), 'settingsUrl' => admin_url( 'admin.php?page=hr-hero-settings' ) ) ) . ';', 'before' );
        wp_register_style( 'hr-hero-style', plugins_url( 'assets/style.css', __FILE__ ), array(), '1.4.0' );
        register_block_type( __DIR__, array( 'render_callback' => array( __CLASS__, 'render' ) ) );
    }

    // Die WordPress-Beitragsbild-Blöcke (auch in Abfrage-Loops) verwenden
    // get_post_thumbnail_id(). Das Filter liefert nur bei der Ausgabe eine
    // virtuelle Ersatz-ID; _thumbnail_id wird dadurch nicht überschrieben.
    public static function thumbnail_fallback( $thumbnail_id, $post ) {
        $choice = get_option( self::SETTING, 'missing' );
        if ( ! in_array( $choice, array( 'missing', 'hero' ), true ) ) { return $thumbnail_id; }
        if ( ! ( $post instanceof WP_Post ) ) { $post = get_post( $post ); }
        if ( ! $post || ! in_array( $post->post_type, self::supported_types(), true ) ) { return $thumbnail_id; }
        // Im Backend soll die Beitragsbild-Seitenleiste den echten Wert zeigen.
        // Das Site-Frontend kann das virtuelle Beitragsbild aber nutzen.
        if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return $thumbnail_id;
        }
        if ( 'missing' === $choice && $thumbnail_id ) { return $thumbnail_id; }
        $hero_id = absint( get_post_meta( $post->ID, self::META, true ) );
        return $hero_id && wp_attachment_is_image( $hero_id ) ? $hero_id : $thumbnail_id;
    }

    public static function settings_menu() {
        add_menu_page( 'Hero-Bild – Einstellungen', 'Hero-Bild', 'manage_options', 'hr-hero-settings', array( __CLASS__, 'settings_page' ), 'dashicons-format-image', 59 );
    }
    /** Einheitliche Standardwerte für alle visuellen Eigenschaften. */
    public static function global_defaults() {
        return array(
            'widthMode' => 'full', 'maxWidth' => 1200,
            'heightDesktop' => 300, 'heightTablet' => 220, 'heightMobile' => 150,
            'objectPosition' => 'center', 'radius' => 0,
            'hoverEffect' => 'zoom', 'zoomPercent' => 4, 'duration' => 400,
            'showImageInfo' => true, 'infoPosition' => 'bottom-right',
            'infoLabel' => 'Informationen zum Bild',
            'infoBackground' => '#062051', 'infoTextColor' => '#ffffff',
            'infoPanelBackground' => '#ffffff', 'infoPanelTextColor' => '#202a36',
            'infoFontSize' => 13, 'infoPadding' => 12,
            'infoOffset' => 12, 'infoRadius' => 6,
        );
    }

    /** Nur bekannte Optionen zulassen, inklusive Begrenzung aller Zahlenwerte. */
    public static function sanitize_global_settings( $value ) {
        $defaults = self::global_defaults();
        if ( ! is_array( $value ) ) { return $defaults; }
        $out = $defaults;
        $selects = array(
            'widthMode' => array( 'full', 'wide', 'content', 'custom' ),
            'objectPosition' => array( 'top', 'center', 'bottom' ),
            'hoverEffect' => array( 'none', 'zoom', 'dim', 'bright' ),
            'infoPosition' => array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ),
        );
        foreach ( $selects as $key => $choices ) {
            if ( isset( $value[$key] ) && is_scalar( $value[$key] ) && in_array( $value[$key], $choices, true ) ) {
                $out[$key] = $value[$key];
            }
        }
        $ranges = array(
            'maxWidth' => array( 200, 3000 ),
            'heightDesktop' => array( 60, 1200 ),
            'heightTablet' => array( 60, 1000 ),
            'heightMobile' => array( 40, 800 ),
            'radius' => array( 0, 100 ),
            'zoomPercent' => array( 0, 20 ),
            'duration' => array( 0, 2000 ),
            'infoFontSize' => array( 10, 26 ),
            'infoPadding' => array( 4, 32 ),
            'infoOffset' => array( 0, 80 ),
            'infoRadius' => array( 0, 40 ),
        );
        foreach ( $ranges as $key => $range ) {
            if ( isset( $value[$key] ) && is_scalar( $value[$key] ) && is_numeric( $value[$key] ) ) {
                $out[$key] = (int) min( $range[1], max( $range[0], (float) $value[$key] ) );
            }
        }
        $out['showImageInfo'] = ! empty( $value['showImageInfo'] );
        if ( isset( $value['infoLabel'] ) && is_string( $value['infoLabel'] ) ) {
            $out['infoLabel'] = sanitize_text_field( $value['infoLabel'] );
        }
        foreach ( array( 'infoBackground', 'infoTextColor', 'infoPanelBackground', 'infoPanelTextColor' ) as $key ) {
            if ( isset( $value[$key] ) && is_string( $value[$key] ) ) {
                $out[$key] = sanitize_hex_color( $value[$key] ) ?: $defaults[$key];
            }
        }
        return $out;
    }

    public static function global_settings() {
        $stored = get_option( self::GLOBAL_SETTING, array() );
        return self::sanitize_global_settings( is_array( $stored ) ? wp_parse_args( $stored, self::global_defaults() ) : self::global_defaults() );
    }

    public static function register_settings() {
        register_setting( 'hr_hero_settings', self::SETTING, array(
            'type' => 'string', 'default' => 'missing',
            'sanitize_callback' => function ( $value ) {
                return in_array( $value, array( 'missing', 'hero', 'off' ), true ) ? $value : 'missing';
            },
        ) );
        register_setting( 'hr_hero_settings', self::DEFAULT_IMAGE, array(
            'type' => 'integer', 'default' => 0,
            'sanitize_callback' => function ( $value ) {
                $id = is_scalar( $value ) ? absint( $value ) : 0;
                return $id && wp_attachment_is_image( $id ) ? $id : 0;
            },
        ) );
        register_setting( 'hr_hero_settings', self::GLOBAL_SETTING, array(
            'type' => 'array', 'default' => self::global_defaults(),
            'sanitize_callback' => array( __CLASS__, 'sanitize_global_settings' ),
        ) );
    }

    private static function setting_row( $key, $label, $type, $values, $help = '', $constraints = array() ) {
        $id = 'hr-hero-' . sanitize_html_class( $key );
        $name = self::GLOBAL_SETTING . '[' . $key . ']';
        $value = isset( $values[$key] ) ? $values[$key] : '';
        echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
        if ( 'select' === $type ) {
            echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
            foreach ( $constraints as $choice => $choice_label ) {
                echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $choice_label ) . '</option>';
            }
            echo '</select>';
        } elseif ( 'checkbox' === $type ) {
            // Hiddenes Feld überträgt auch den ausgeschalteten Zustand.
            echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
            echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, true, false ) . '>';
        } elseif ( 'color' === $type ) {
            echo '<input type="color" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
        } elseif ( 'number' === $type ) {
            echo '<input type="number" class="small-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( $constraints[0] ) . '" max="' . esc_attr( $constraints[1] ) . '" step="1"> ' . esc_html( 'duration' === $key ? 'ms' : ( 'zoomPercent' === $key ? '%' : 'px' ) );
        } else {
            echo '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
        }
        if ( $help ) { echo '<p class="description">' . esc_html( $help ) . '</p>'; }
        echo '</td></tr>';
    }

    public static function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $values = self::global_settings();
        $choice = get_option( self::SETTING, 'missing' );
        echo '<div class="wrap"><h1>Hero-Bild – globale Einstellungen</h1>';
        echo '<p>Diese Einstellungen steuern die Darstellung aller Hero-Bild-Blöcke, solange im jeweiligen Block „Globale Einstellungen verwenden“ aktiviert ist. Bildzuordnung und Bildquelle bleiben pro Beitrag, Seite oder Kategorie individuell.</p>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'hr_hero_settings' );

        echo '<h2>Einheitliches Symbolbild</h2>';
        echo '<p>Für jeden Hero-Bild-Block gilt: Hero-Bild → Beitragsbild → Symbolbild. Das gilt auch bei individuellen Blockeinstellungen und in Abfrage-Loops. Für Kategorie-Archive gilt: Kategorie-Hero-Bild → Symbolbild.</p>';
        $default_id = self::default_image_id();
        echo '<div id="hr-hero-default-preview">' . ( $default_id ? wp_get_attachment_image( $default_id, 'medium' ) : '' ) . '</div>';
        echo '<input type="hidden" id="hr-hero-default-id" name="' . esc_attr( self::DEFAULT_IMAGE ) . '" value="' . esc_attr( $default_id ) . '">';
        echo '<p><button type="button" class="button" id="hr-hero-default-choose">Symbolbild auswählen</button> <button type="button" class="button" id="hr-hero-default-remove">Entfernen</button></p>';

        echo '<h2>Bildgröße und Ausschnitt</h2><table class="form-table" role="presentation">';
        self::setting_row( 'widthMode', 'Bildbreite', 'select', $values, '', array( 'full' => 'Volle verfügbare Breite', 'wide' => 'Weite Inhaltsbreite', 'content' => 'Normale Inhaltsbreite', 'custom' => 'Eigene maximale Breite' ) );
        self::setting_row( 'maxWidth', 'Maximale Breite bei eigener Breite', 'number', $values, '', array( 200, 3000 ) );
        self::setting_row( 'heightDesktop', 'Höhe Desktop', 'number', $values, 'Über 1024 px Bildschirmbreite.', array( 60, 1200 ) );
        self::setting_row( 'heightTablet', 'Höhe Tablet', 'number', $values, 'Bis einschließlich 1024 px Bildschirmbreite.', array( 60, 1000 ) );
        self::setting_row( 'heightMobile', 'Höhe Smartphone', 'number', $values, 'Bis einschließlich 600 px Bildschirmbreite.', array( 40, 800 ) );
        self::setting_row( 'objectPosition', 'Bildausschnitt', 'select', $values, '', array( 'top' => 'Oben', 'center' => 'Mitte', 'bottom' => 'Unten' ) );
        self::setting_row( 'radius', 'Eckenradius', 'number', $values, '', array( 0, 100 ) );
        echo '</table>';

        echo '<h2>Hover-Effekt</h2><table class="form-table" role="presentation">';
        self::setting_row( 'hoverEffect', 'Effekt', 'select', $values, '', array( 'none' => 'Keiner', 'zoom' => 'Sanfter Zoom', 'dim' => 'Abdunkeln', 'bright' => 'Aufhellen' ) );
        self::setting_row( 'zoomPercent', 'Zoomstärke (%)', 'number', $values, 'Nur beim Zoom-Effekt wirksam.', array( 0, 20 ) );
        self::setting_row( 'duration', 'Animationsdauer', 'number', $values, 'In Millisekunden: 400 entspricht 0,4 Sekunden.', array( 0, 2000 ) );
        echo '</table>';

        echo '<h2>Bildinformationen</h2><table class="form-table" role="presentation">';
        self::setting_row( 'showImageInfo', 'Infokasten anzeigen', 'checkbox', $values );
        self::setting_row( 'infoLabel', 'Schaltflächen-Text', 'text', $values );
        self::setting_row( 'infoPosition', 'Position', 'select', $values, '', array( 'bottom-right' => 'Unten rechts', 'bottom-left' => 'Unten links', 'top-right' => 'Oben rechts', 'top-left' => 'Oben links' ) );
        self::setting_row( 'infoFontSize', 'Schriftgröße', 'number', $values, '', array( 10, 26 ) );
        self::setting_row( 'infoPadding', 'Innenabstand', 'number', $values, '', array( 4, 32 ) );
        self::setting_row( 'infoOffset', 'Abstand zum Bildrand', 'number', $values, '', array( 0, 80 ) );
        self::setting_row( 'infoRadius', 'Eckenradius Infokasten', 'number', $values, '', array( 0, 40 ) );
        self::setting_row( 'infoBackground', 'Schaltfläche: Hintergrund', 'color', $values );
        self::setting_row( 'infoTextColor', 'Schaltfläche: Text', 'color', $values );
        self::setting_row( 'infoPanelBackground', 'Informationsfeld: Hintergrund', 'color', $values );
        self::setting_row( 'infoPanelTextColor', 'Informationsfeld: Text', 'color', $values );
        echo '</table>';

        echo '<h2>Beitragsbild in Abfrage-Loops</h2>';
        echo '<p>Wähle aus, welches Bild der WordPress-Block „Beitragsbild“ verwendet. Hero-Bild-Blöcke verwenden immer Hero-Bild → Beitragsbild → Symbolbild.</p>';
        $choices = array(
            'missing' => 'Zuerst Beitragsbild, sonst Hero-Bild (empfohlen)',
            'hero' => 'Zuerst Hero-Bild, sonst Beitragsbild',
            'off' => 'Nur normales Beitragsbild (Hero-Ersatz deaktiviert)',
        );
        foreach ( $choices as $val => $label ) {
            echo '<p><label><input type="radio" name="' . esc_attr( self::SETTING ) . '" value="' . esc_attr( $val ) . '" ' . checked( $choice, $val, false ) . '> ' . esc_html( $label ) . '</label></p>';
        }
        echo '<p class="description">Die tatsächlichen Beitragsbilder werden nicht überschrieben. Externe Loop-Plugins können abweichen.</p>';
        submit_button( 'Einstellungen speichern' );
        echo '</form><hr>';
        echo '<p><strong>Hinweis:</strong> Bereits angelegte Hero-Bild-Blöcke verwenden nach dem Update standardmäßig die globalen Einstellungen. Im Site-Editor kannst du beim jeweiligen Block „Globale Einstellungen verwenden“ ausschalten und seine bisherigen individuellen Werte weiterverwenden.</p>';
        echo '<p>Hero-Bilder selbst weist du weiterhin im Beitragseditor bzw. bei Beiträge → Kategorien zu. Im Kategorie-Template den Block auf die Bildquelle „Aktuelle Kategorie / Taxonomie“ stellen.</p>';
        echo '</div>';
    }

    private static function term_picker( $image_id, $kind ) {
        $image = $image_id ? wp_get_attachment_image( $image_id, 'medium', false, array( 'style' => 'max-width:100%;height:auto;display:block;margin:0 0 8px;' ) ) : '';
        echo '<div class="hr-hero-term-picker" data-context="' . esc_attr( $kind ) . '">';
        echo '<div class="hr-hero-term-preview">' . wp_kses_post( $image ) . '</div>';
        echo '<input type="hidden" name="hr_hero_term_image_id" value="' . esc_attr( $image_id ) . '" class="hr-hero-term-image-id">';
        echo '<button type="button" class="button hr-hero-term-choose">Bild auswählen</button> ';
        echo '<button type="button" class="button hr-hero-term-remove"' . ( $image_id ? '' : ' style="display:none"' ) . '>Entfernen</button>';
        echo '<p class="description">Wird automatisch auf der Archivseite dieser Kategorie angezeigt. Der Hero-Block im Archiv-Template übernimmt Höhe, Breite, Hover und Bildinformationen.</p>';
        echo '</div>';
    }
    public static function term_add_field( $taxonomy ) {
        echo '<div class="form-field"><label>Hero-Bild</label>';
        wp_nonce_field( 'hr_hero_term_save', 'hr_hero_term_nonce' );
        self::term_picker( 0, 'add' );
        echo '</div>';
    }
    public static function term_edit_field( $term ) {
        $image_id = absint( get_term_meta( $term->term_id, self::TERM_META, true ) );
        echo '<tr class="form-field"><th scope="row"><label>Hero-Bild</label></th><td>';
        wp_nonce_field( 'hr_hero_term_save', 'hr_hero_term_nonce' );
        self::term_picker( $image_id, 'edit' );
        echo '</td></tr>';
    }
    public static function save_term_field( $term_id ) {
        if ( ! isset( $_POST['hr_hero_term_nonce'], $_POST['hr_hero_term_image_id'] ) ) { return; }
        $nonce = sanitize_text_field( wp_unslash( $_POST['hr_hero_term_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'hr_hero_term_save' ) || ! current_user_can( 'edit_term', $term_id ) ) { return; }
        $image_id = absint( wp_unslash( $_POST['hr_hero_term_image_id'] ) );
        if ( $image_id && wp_attachment_is_image( $image_id ) ) {
            update_term_meta( $term_id, self::TERM_META, $image_id );
        } else {
            delete_term_meta( $term_id, self::TERM_META );
        }
    }

    /**
     * Bestimmt die Bild-ID anhand des tatsächlichen Seitenkontexts.
     *
     * Auf einem Kategorien- oder Schlagwortarchiv kann WordPress neben der
     * aufgerufenen WP_Term auch einen postId-Kontext weiterreichen. Deshalb
     * wird bei Archivansichten zunächst die tatsächlich abgefragte Taxonomie
     * berücksichtigt, nicht irgendein Beitrag innerhalb des Templates.
     *
     * Der optionale Modus „Beitrag/Seite“ bleibt für den Einsatz innerhalb
     * von Post-Template-/Query-Loop-Blöcken erhalten.
     */
    private static function current_hero_image_id( $block, $attributes = array() ) {
        $source = isset( $attributes['imageSource'] ) ? $attributes['imageSource'] : 'auto';
        if ( ! in_array( $source, array( 'auto', 'term', 'post' ), true ) ) {
            $source = 'auto';
        }

        $context = isset( $block->context ) && is_array( $block->context ) ? $block->context : array();
        $context_post_id = ! empty( $context['postId'] ) ? absint( $context['postId'] ) : 0;
        $has_post_context = $context_post_id && in_array( get_post_type( $context_post_id ), self::supported_types(), true );
        // In einem Query-Loop bezieht sich postId auf den einzelnen Datensatz.
        $inside_post_loop = $has_post_context && array_key_exists( 'queryId', $context );

        $is_term_archive = is_category() || is_tag() || is_tax();
        $queried = get_queried_object();
        $queried_term = ( $queried instanceof WP_Term && in_array( $queried->taxonomy, self::supported_taxonomies(), true ) ) ? $queried : null;
        // WP_Term ist die verlässlichere Quelle als das globale $post-Objekt.
        $use_term = 'term' === $source || (
            'auto' === $source && ! $inside_post_loop && ( $is_term_archive || ( $queried_term && ! is_singular() ) )
        );

        if ( $use_term ) {
            $term_image = $queried_term ? absint( get_term_meta( $queried_term->term_id, self::TERM_META, true ) ) : 0;
            return $term_image && wp_attachment_is_image( $term_image ) ? $term_image : self::default_image_id();
        }

        if ( $has_post_context ) {
            return self::post_hero_image_id( $context_post_id );
        }

        // Auf Archiven ohne passenden Term niemals das Hero-Bild des ersten
        // Beitrags im Loop als vermeintliches Archivbild verwenden.
        if ( ! is_singular() && 'post' !== $source ) {
            return self::default_image_id();
        }

        $post_id = is_singular() ? absint( get_queried_object_id() ) : 0;
        if ( $post_id && in_array( get_post_type( $post_id ), self::supported_types(), true ) ) {
            return self::post_hero_image_id( $post_id );
        }
        return self::default_image_id();
    }

    /** Für Seiten und Beiträge in jedem Kontext: Hero → Beitragsbild → Symbolbild. */
    private static function post_hero_image_id( $post_id ) {
        $hero_id = absint( get_post_meta( $post_id, self::META, true ) );
        if ( $hero_id && wp_attachment_is_image( $hero_id ) ) { return $hero_id; }
        // Rohwert verwenden, damit der globale Beitragsbild-Ersatz nicht eingreift.
        $thumbnail_id = absint( get_post_meta( $post_id, '_thumbnail_id', true ) );
        return $thumbnail_id && wp_attachment_is_image( $thumbnail_id ) ? $thumbnail_id : self::default_image_id();
    }

    /** Das Symbolbild gilt unabhängig von den visuellen Blockeinstellungen. */
    private static function default_image_id() {
        $id = absint( get_option( self::DEFAULT_IMAGE, 0 ) );
        return $id && wp_attachment_is_image( $id ) ? $id : 0;
    }

    private static function numeric( $value, $fallback, $minimum, $maximum ) {
        return min( $maximum, max( $minimum, is_numeric( $value ) ? (float) $value : $fallback ) );
    }
    public static function render( $attributes, $content, $block ) {
        $image_id = self::current_hero_image_id( $block, $attributes );
        if ( ! $image_id || ! wp_attachment_is_image( $image_id ) ) { return ''; }
        // Globale Darstellung wird serverseitig geladen, auch für bereits
        // veröffentlichte Templates. Individuelle Einstellungen bleiben erhalten
        // und lassen sich über den Block-Schalter wieder verwenden.
        $use_global = ! isset( $attributes['useGlobalSettings'] ) || (bool) $attributes['useGlobalSettings'];
        $a = $use_global
            ? self::global_settings()
            : wp_parse_args( $attributes, self::global_defaults() );
        $width_mode = in_array( $a['widthMode'], array( 'full', 'wide', 'content', 'custom' ), true ) ? $a['widthMode'] : 'full';
        $position = in_array( $a['objectPosition'], array( 'top', 'center', 'bottom' ), true ) ? $a['objectPosition'] : 'center';
        $effect = in_array( $a['hoverEffect'], array( 'none', 'zoom', 'dim', 'bright' ), true ) ? $a['hoverEffect'] : 'zoom';
        $max_width = self::numeric( $a['maxWidth'], 1200, 200, 3000 );
        $style = sprintf(
            '--hr-desktop:%dpx;--hr-tablet:%dpx;--hr-mobile:%dpx;--hr-radius:%dpx;--hr-zoom:%s;--hr-duration:%dms;--hr-max-width:%dpx;',
            self::numeric( $a['heightDesktop'], 300, 60, 1200 ),
            self::numeric( $a['heightTablet'], 220, 60, 1000 ),
            self::numeric( $a['heightMobile'], 150, 40, 800 ),
            self::numeric( $a['radius'], 0, 0, 100 ),
            esc_attr( (string) ( 1 + self::numeric( $a['zoomPercent'], 4, 0, 20 ) / 100 ) ),
            self::numeric( $a['duration'], 400, 0, 2000 ),
            $max_width
        );
        $info_position = in_array( $a['infoPosition'], array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ), true ) ? $a['infoPosition'] : 'bottom-right';
        $info_colors = array();
        foreach ( array( 'infoBackground', 'infoTextColor', 'infoPanelBackground', 'infoPanelTextColor' ) as $color_key ) {
            $fallback = array( 'infoBackground' => '#062051', 'infoTextColor' => '#ffffff', 'infoPanelBackground' => '#ffffff', 'infoPanelTextColor' => '#202a36' );
            $info_colors[$color_key] = sanitize_hex_color( $a[$color_key] ) ?: $fallback[$color_key];
        }
        $style .= sprintf(
            '--hr-info-bg:%s;--hr-info-color:%s;--hr-info-panel-bg:%s;--hr-info-panel-color:%s;--hr-info-font:%dpx;--hr-info-padding:%dpx;--hr-info-offset:%dpx;--hr-info-radius:%dpx;',
            $info_colors['infoBackground'], $info_colors['infoTextColor'], $info_colors['infoPanelBackground'], $info_colors['infoPanelTextColor'],
            self::numeric( $a['infoFontSize'], 13, 10, 26 ), self::numeric( $a['infoPadding'], 12, 4, 32 ),
            self::numeric( $a['infoOffset'], 12, 0, 80 ), self::numeric( $a['infoRadius'], 6, 0, 40 )
        );
        // Die Ausrichtung muss auf dem tatsächlichen Block-Wrapper liegen. Sonst
        // beschränkt WordPress' constrained layout den Block auf die Inhaltsbreite.
        $alignment = 'full' === $width_mode ? 'alignfull' : ( 'wide' === $width_mode ? 'alignwide' : '' );
        $classes = array( 'hr-hero', 'hr-hero--' . $width_mode, 'hr-hero--' . $effect );
        if ( $alignment ) { $classes[] = $alignment; }
        if ( ! empty( $a['className'] ) ) { $classes[] = sanitize_html_class( $a['className'] ); }
        $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
        // Dekoratives Bild, sofern kein Alternativtext in der Mediathek hinterlegt wurde.
        $img = wp_get_attachment_image( $image_id, 'full', false, array(
            'class' => 'hr-hero__image', 'alt' => $alt ? $alt : '',
            'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async',
            'style' => 'object-position:' . $position . ';',
        ) );
        if ( ! $img ) { return ''; }
        $wrapper = get_block_wrapper_attributes( array(
            'class' => implode( ' ', $classes ),
            'style' => $style,
        ) );
        $information = '';
        if ( ! empty( $a['showImageInfo'] ) ) {
            $attachment = get_post( $image_id );
            $image_title = $attachment ? trim( $attachment->post_title ) : '';
            $caption = $attachment ? trim( $attachment->post_excerpt ) : '';
            // Leere Felder nicht als leere Zeilen ausgeben.
            if ( '' !== $image_title || '' !== $caption ) {
                $label = sanitize_text_field( $a['infoLabel'] );
                if ( '' === trim( $label ) ) { $label = __( 'Informationen zum Bild', 'hero-bild' ); }
                $information = '<details class="hr-hero__info hr-hero__info--' . esc_attr( $info_position ) . '">';
                $information .= '<summary class="hr-hero__info-trigger">' . esc_html( $label ) . '</summary>';
                $information .= '<div class="hr-hero__info-panel">';
                if ( '' !== $image_title ) { $information .= '<div class="hr-hero__info-title">' . esc_html( $image_title ) . '</div>'; }
                if ( '' !== $caption ) { $information .= '<div class="hr-hero__info-description">' . wp_kses_post( wpautop( $caption ) ) . '</div>'; }
                $information .= '</div></details>';
            }
        }
        return '<div ' . $wrapper . '><div class="hr-hero__clip">' . $img . '</div>' . $information . '</div>';
    }
}
HR_Hero_Bild::init();
