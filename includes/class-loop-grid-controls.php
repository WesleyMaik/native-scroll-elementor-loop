<?php

declare(strict_types=1);

namespace NativeScrollLoop;

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Loop_Grid_Controls
{
    public function register(Controls_Stack $widget): void
    {
        $this->register_content_controls($widget);
        $this->register_style_controls($widget);
    }

    private function register_content_controls(Controls_Stack $widget): void
    {
        $widget->start_controls_section(
            'section_native_scroll_loop',
            [
                'label' => esc_html__('Native Scroll Carousel', 'native-scroll-loop'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $widget->add_control(
            'nsl_enabled',
            [
                'label' => esc_html__('Enable native horizontal carousel', 'native-scroll-loop'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'frontend_available' => true,
                'render_type' => 'template',
            ]
        );

        $widget->add_control(
            'nsl_masonry_warning',
            [
                'type' => Controls_Manager::ALERT,
                'alert_type' => 'warning',
                'content' => esc_html__('Native Scroll Carousel is disabled while Masonry is enabled.', 'native-scroll-loop'),
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'masonry' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_alternate_template_warning',
            [
                'type' => Controls_Manager::ALERT,
                'alert_type' => 'warning',
                'content' => esc_html__('Native Scroll Carousel is disabled when an alternate template spans more than one column.', 'native-scroll-loop'),
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'alternate_template' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_general_heading',
            [
                'label' => esc_html__('General', 'native-scroll-loop'),
                'type' => Controls_Manager::HEADING,
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control('nsl_snap_enabled', $this->switcher(esc_html__('Enable CSS Scroll Snap', 'native-scroll-loop'), 'yes'));

        $widget->add_control(
            'nsl_snap_strictness',
            [
                'label' => esc_html__('Snap strictness', 'native-scroll-loop'),
                'type' => Controls_Manager::SELECT,
                'default' => 'mandatory',
                'options' => [
                    'mandatory' => esc_html__('Mandatory', 'native-scroll-loop'),
                    'proximity' => esc_html__('Proximity', 'native-scroll-loop'),
                ],
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_snap_enabled' => 'yes',
                ],
            ]
        );

        $widget->add_control('nsl_snap_stop', $this->switcher(esc_html__('Stop at each snap point', 'native-scroll-loop')));

        $widget->add_control(
            'nsl_scroll_behavior',
            [
                'label' => esc_html__('Scroll behavior', 'native-scroll-loop'),
                'type' => Controls_Manager::SELECT,
                'default' => 'smooth',
                'options' => [
                    'smooth' => esc_html__('Smooth', 'native-scroll-loop'),
                    'instant' => esc_html__('Instant', 'native-scroll-loop'),
                ],
                'render_type' => 'template',
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control(
            'nsl_arrow_advance',
            [
                'label' => esc_html__('Arrow advance', 'native-scroll-loop'),
                'type' => Controls_Manager::SELECT,
                'default' => 'item',
                'options' => [
                    'item' => esc_html__('One item', 'native-scroll-loop'),
                    'group' => esc_html__('One visible group', 'native-scroll-loop'),
                ],
                'render_type' => 'template',
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control(
            'nsl_aria_label',
            [
                'label' => esc_html__('Carousel accessible label', 'native-scroll-loop'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Scrollable items', 'native-scroll-loop'),
                'render_type' => 'template',
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control(
            'nsl_navigation_heading',
            [
                'label' => esc_html__('Navigation', 'native-scroll-loop'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control('nsl_show_arrows', $this->switcher(esc_html__('Show arrows', 'native-scroll-loop'), 'yes'));

        $widget->add_control(
            'nsl_previous_icon',
            [
                'label' => esc_html__('Previous icon', 'native-scroll-loop'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'eicon-chevron-left',
                    'library' => 'eicons',
                ],
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_show_arrows' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_next_icon',
            [
                'label' => esc_html__('Next icon', 'native-scroll-loop'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'eicon-chevron-right',
                    'library' => 'eicons',
                ],
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_show_arrows' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_arrow_position',
            [
                'label' => esc_html__('Arrow position', 'native-scroll-loop'),
                'type' => Controls_Manager::SELECT,
                'default' => 'top-right',
                'options' => [
                    'top-right' => esc_html__('Top right', 'native-scroll-loop'),
                    'bottom-right' => esc_html__('Bottom right', 'native-scroll-loop'),
                    'split-sides' => esc_html__('Split sides', 'native-scroll-loop'),
                ],
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_show_arrows' => 'yes',
                ],
            ]
        );

        $widget->add_control('nsl_hide_arrows_mobile', $this->switcher(esc_html__('Hide arrows on mobile', 'native-scroll-loop'), 'yes'));
        $widget->add_control(
            'nsl_disable_unavailable_arrows',
            array_merge(
                $this->switcher(esc_html__('Disable unavailable arrows', 'native-scroll-loop'), 'yes'),
                [
                    'description' => esc_html__('When disabled, Next at the end returns to the beginning and Previous at the beginning goes to the end.', 'native-scroll-loop'),
                ]
            )
        );

        $widget->add_control(
            'nsl_progress_heading',
            [
                'label' => esc_html__('Progress', 'native-scroll-loop'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control('nsl_show_progress', $this->switcher(esc_html__('Show progress', 'native-scroll-loop'), 'yes'));

        $widget->add_control(
            'nsl_progress_mode',
            [
                'label' => esc_html__('Progress mode', 'native-scroll-loop'),
                'type' => Controls_Manager::SELECT,
                'default' => 'bar',
                'options' => [
                    'bar' => esc_html__('Growing bar', 'native-scroll-loop'),
                    'thumb' => esc_html__('Scrollbar thumb', 'native-scroll-loop'),
                    'dots' => esc_html__('Dots', 'native-scroll-loop'),
                ],
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_show_progress' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_progress_placement',
            [
                'label' => esc_html__('Progress placement', 'native-scroll-loop'),
                'type' => Controls_Manager::SELECT,
                'default' => 'below',
                'options' => [
                    'below' => esc_html__('Below carousel', 'native-scroll-loop'),
                    'beside-navigation' => esc_html__('Beside navigation', 'native-scroll-loop'),
                ],
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_show_progress' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_autoplay_heading',
            [
                'label' => esc_html__('Autoplay', 'native-scroll-loop'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control('nsl_autoplay', $this->switcher(esc_html__('Enable autoplay', 'native-scroll-loop')));

        $widget->add_control(
            'nsl_autoplay_interval',
            [
                'label' => esc_html__('Interval (milliseconds)', 'native-scroll-loop'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5000,
                'min' => 1500,
                'max' => 60000,
                'step' => 100,
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_autoplay' => 'yes',
                ],
            ]
        );

        $widget->add_control('nsl_pause_on_hover', $this->switcher(esc_html__('Pause on hover', 'native-scroll-loop'), 'yes'));
        $widget->add_control('nsl_pause_on_focus', $this->switcher(esc_html__('Pause on focus', 'native-scroll-loop'), 'yes'));
        $widget->add_control('nsl_pause_on_interaction', $this->switcher(esc_html__('Pause on pointer or touch interaction', 'native-scroll-loop'), 'yes'));
        $widget->add_control('nsl_pause_when_hidden', $this->switcher(esc_html__('Pause while browser tab is hidden', 'native-scroll-loop'), 'yes'));

        $widget->add_control(
            'nsl_resume_delay',
            [
                'label' => esc_html__('Resume delay (milliseconds)', 'native-scroll-loop'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1200,
                'min' => 0,
                'max' => 60000,
                'step' => 100,
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_autoplay' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_autoplay_end_behavior',
            [
                'label' => esc_html__('End behavior', 'native-scroll-loop'),
                'type' => Controls_Manager::SELECT,
                'default' => 'rewind',
                'options' => [
                    'rewind' => esc_html__('Return to beginning', 'native-scroll-loop'),
                    'stop' => esc_html__('Stop at end', 'native-scroll-loop'),
                ],
                'render_type' => 'template',
                'condition' => [
                    'nsl_enabled' => 'yes',
                    'nsl_autoplay' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'nsl_responsive_heading',
            [
                'label' => esc_html__('Responsive layout', 'native-scroll-loop'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_responsive_control(
            'nsl_mobile_item_width',
            [
                'label' => esc_html__('Item width override', 'native-scroll-loop'),
                'description' => esc_html__('Leave desktop and tablet empty to reuse the Loop Grid column controls.', 'native-scroll-loop'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%', 'px', 'vw'],
                'range' => [
                    '%' => ['min' => 20, 'max' => 100],
                    'px' => ['min' => 120, 'max' => 1200],
                    'vw' => ['min' => 20, 'max' => 100],
                ],
                'default' => [],
                'tablet_default' => [],
                'mobile_default' => ['size' => 78, 'unit' => '%'],
                'render_type' => 'template',
                'selectors' => [
                    '{{WRAPPER}}' => '--native-scroll-loop-item-width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->end_controls_section();
    }

    private function register_style_controls(Controls_Stack $widget): void
    {
        $widget->start_controls_section(
            'section_native_scroll_loop_style',
            [
                'label' => esc_html__('Native Scroll Carousel', 'native-scroll-loop'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => ['nsl_enabled' => 'yes'],
            ]
        );

        $widget->add_control('nsl_arrow_style_heading', ['label' => esc_html__('Arrows', 'native-scroll-loop'), 'type' => Controls_Manager::HEADING]);
        $this->add_color_control($widget, 'nsl_arrow_icon_color', esc_html__('Icon color', 'native-scroll-loop'), '--native-scroll-loop-arrow-color');
        $this->add_color_control($widget, 'nsl_arrow_background', esc_html__('Background', 'native-scroll-loop'), '--native-scroll-loop-arrow-background');
        $this->add_color_control($widget, 'nsl_arrow_border_color', esc_html__('Border color', 'native-scroll-loop'), '--native-scroll-loop-arrow-border-color');
        $this->add_color_control($widget, 'nsl_arrow_hover_icon_color', esc_html__('Hover icon color', 'native-scroll-loop'), '--native-scroll-loop-arrow-hover-color');
        $this->add_color_control($widget, 'nsl_arrow_hover_background', esc_html__('Hover background', 'native-scroll-loop'), '--native-scroll-loop-arrow-hover-background');
        $this->add_color_control($widget, 'nsl_arrow_hover_border_color', esc_html__('Hover border color', 'native-scroll-loop'), '--native-scroll-loop-arrow-hover-border-color');
        $this->add_slider_control($widget, 'nsl_arrow_size', esc_html__('Button size', 'native-scroll-loop'), '--native-scroll-loop-arrow-size', 24, 96, 48);
        $this->add_slider_control($widget, 'nsl_arrow_icon_size', esc_html__('Icon size', 'native-scroll-loop'), '--native-scroll-loop-arrow-icon-size', 8, 48, 18);
        $this->add_slider_control($widget, 'nsl_arrow_border_width', esc_html__('Border width', 'native-scroll-loop'), '--native-scroll-loop-arrow-border-width', 0, 10, 1);
        $this->add_slider_control($widget, 'nsl_arrow_border_radius', esc_html__('Border radius', 'native-scroll-loop'), '--native-scroll-loop-arrow-radius', 0, 100, 100);
        $this->add_slider_control($widget, 'nsl_navigation_spacing', esc_html__('Navigation spacing', 'native-scroll-loop'), '--native-scroll-loop-navigation-spacing', 0, 100, 20);

        $widget->add_control(
            'nsl_progress_style_heading',
            [
                'label' => esc_html__('Progress', 'native-scroll-loop'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_color_control($widget, 'nsl_progress_color', esc_html__('Progress color', 'native-scroll-loop'), '--native-scroll-loop-progress-color');
        $this->add_color_control($widget, 'nsl_progress_track_color', esc_html__('Track color', 'native-scroll-loop'), '--native-scroll-loop-progress-track-color');
        $this->add_slider_control($widget, 'nsl_progress_height', esc_html__('Height', 'native-scroll-loop'), '--native-scroll-loop-progress-height', 1, 20, 3);
        $this->add_slider_control($widget, 'nsl_progress_spacing', esc_html__('Spacing', 'native-scroll-loop'), '--native-scroll-loop-progress-spacing', 0, 100, 20);
        $this->add_slider_control($widget, 'nsl_progress_thumb_min_width', esc_html__('Minimum thumb width', 'native-scroll-loop'), '--native-scroll-loop-thumb-min-width', 16, 200, 40);
        $widget->add_control(
            'nsl_progress_dot_size',
            [
                'label' => esc_html__('Dot size', 'native-scroll-loop'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => ['px' => ['min' => 4, 'max' => 32]],
                'default' => ['size' => 8, 'unit' => 'px'],
                'selectors' => ['{{WRAPPER}}' => '--native-scroll-loop-dot-size: {{SIZE}}{{UNIT}};'],
                'condition' => ['nsl_progress_mode' => 'dots'],
            ]
        );
        $widget->add_control(
            'nsl_progress_dot_gap',
            [
                'label' => esc_html__('Dot spacing', 'native-scroll-loop'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => ['px' => ['min' => 0, 'max' => 40]],
                'default' => ['size' => 8, 'unit' => 'px'],
                'selectors' => ['{{WRAPPER}}' => '--native-scroll-loop-dot-gap: {{SIZE}}{{UNIT}};'],
                'condition' => ['nsl_progress_mode' => 'dots'],
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * @return array<string, mixed>
     */
    private function switcher(string $label, string $default = ''): array
    {
        return [
            'label' => $label,
            'type' => Controls_Manager::SWITCHER,
            'default' => $default,
            'render_type' => 'template',
            'condition' => ['nsl_enabled' => 'yes'],
        ];
    }

    private function add_color_control(Controls_Stack $widget, string $id, string $label, string $variable): void
    {
        $widget->add_control(
            $id,
            [
                'label' => $label,
                'type' => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}}' => $variable . ': {{VALUE}};'],
            ]
        );
    }

    private function add_slider_control(
        Controls_Stack $widget,
        string $id,
        string $label,
        string $variable,
        int $minimum,
        int $maximum,
        int $default
    ): void {
        $widget->add_control(
            $id,
            [
                'label' => $label,
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => ['px' => ['min' => $minimum, 'max' => $maximum]],
                'default' => ['size' => $default, 'unit' => 'px'],
                'selectors' => ['{{WRAPPER}}' => $variable . ': {{SIZE}}{{UNIT}};'],
            ]
        );
    }
}
