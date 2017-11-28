<?php

class RightsStatementsPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Plugin hooks.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'config',
        'config_form',
        'admin_items_browse_detailed_each'
    );

    /**
     * @var array Plugin filters.
     */
    protected $_filters = array(
        'rightsStatement' => array('Display', 'Item', 'Dublin Core', 'Rights')
    );

    /**
     * @var array Plugin options.
     */
    protected $_options = array(
        'rights_statements_preference' => ''
    );

    /**
     * Domains with rights statements and accompanying details.
     * @var array
     */
    private $domains = array(
        'rightsstatements.org' => array(
            'pattern' => '{^/(page|vocab)/([A-Z-]+)/([\d.]+)/?$}i',
            'licenses' => array(
                'CNE' => 'Copyright Not Evaluated',
                'InC' =>  'In Copyright',
                'InC-EDU' => 'In Copyright - Educational Use Permitted',
                'InC-NC' => 'In Copyright - Non-Commercial Use Permitted',
                'InC-OW-EU' => 'In Copyright - EU Orphan Work',
                'InC-RUU' => 'In Copyright - Unknown Rightsholder',
                'NKC' => 'No Known Copyright',
                'NoC-CR' => 'No Copyright - Contractual Restrictions',
                'NoC-NC' => 'No Copyright - Non-Commerical Use Only',
                'NoC-OKLR' => 'No Copyright - Other Legal Restrictions',
                'NoC-US' => 'No Copyright - In the United States',
                'UND' => 'Copyright Undetermined'
            ),
            'formats' => array(
                'dark-white-interior-blue-type' => 50,
                'dark-white-interior' => 50,
                'dark' => 50,
                'white' => 50
            )
        ),
        'creativecommons.org' => array(
            'pattern' => '{^/(licenses|publicdomain)/([A-Z-]+)/([\d.]+)/?$}i',
            'licenses' => array(
                'by' => 'CC BY',
                'by-nc' => 'CC BY-NC',
                'by-nc-nd' => 'CC BY-NC-ND',
                'by-nc-sa' => 'CC BY-NC-SA',
                'by-nd' => 'CC BY-ND',
                'by-sa' => 'CC BY-SA',
                'mark' => 'Public Domain Mark',
                'zero' => 'CC0 Public Domain'
            ),
            'formats' => array(
                '88x31' => 31,
                '80x15' => 15
            ),
            'format' => '88x31',
            'height' => 31
        )
    );

    /**
     * Plugin constructor.
     *
     * Defines options for domains, and calls parent constructor.
     */
    public function __construct()
    {
        foreach ($this->domains as $domain => $data) {
            $prefix = 'rights_statements_' . str_replace('.', '_', $domain);
            $this->_options[$prefix . '_height'] = reset($data['formats']);
            $this->_options[$prefix . '_format'] = key($data['formats']);
        }

        parent::__construct();
    }

    /**
     * Hook to plugin installation.
     *
     * Installs the options for the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();
    }

    /**
     * Hook to plugin uninstallation.
     *
     * Uninstalls the options for the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Hook to plugin upgrade.
     *
     * @param array $args Contains: `old_version` and `new_version`.
     */
    public function hookUpgrade($args)
    {
        if (version_compare($args['old_version'], '1.1', '<=')) {
            set_option('rights_statements_preference', '');
        }
    }

    /**
     * Hook to plugin configuration form submission.
     *
     * Sets options submitted by the configuration form.
     */
    public function hookConfig($args)
    {
        foreach (array_keys($this->_options) as $option) {
            if (isset($args['post'][$option])) {
                set_option($option, $args['post'][$option]);
            }
        }
    }

    /**
     * Hook to output plugin configuration form.
     *
     * Include form from config_form.php file.
     */
    public function hookConfigForm()
    {
        include 'config_form.php';
    }

    public function hookAdminItemsBrowseDetailedEach($args)
    {
        $output = array();

        foreach ($this->getRights($args['item']) as $right) {
            $output[] = $right['license'];
        }

        if ($output) {
            echo '<div class="rights-statements"><strong>Rights:</strong> ';
            echo implode(', ', $output);
            echo '</div>';
        }
    }

    public function rightsStatement($text, $args)
    {
        if (is_admin_theme()) {
            return $text;
        }

        $rights = $this->getRights($args['record']);

        if (!isset($rights[$text])) {
            return $text;
        }

        $pref = get_option('rights_statements_preference');

        if ($pref) {
            foreach ($rights as $right) {
                if ($right['domain'] === $pref && $right !== $rights[$text]) {
                    return '';
                }
            }
        }

        return
            '<p class="rights-statements">' .
            '<a href="http://'.
                $rights[$text]['domain'] . '/' .
                $rights[$text]['matches'][1] . '/' .
                $rights[$text]['matches'][2] . '/' .
                $rights[$text]['matches'][3] . '/">' .
            '<img src="' . web_path_to(
                $rights[$text]['domain'] . '/' .
                $rights[$text]['format'] . '/' .
                $rights[$text]['matches'][2] . '.svg'
            ). '" alt="' .
                $rights[$text]['license'] . '" height="' .
                $rights[$text]['height'] . '"></a></p>';
    }

    private function getRights($record)
    {
        $rights = array();

        $texts = metadata(
            $record,
            array('Dublin Core', 'Rights'),
            array('all' => true, 'no_escape' => true, 'no_filter' => true)
        );

        foreach ($texts as $text) {
            if (!filter_var($text, FILTER_VALIDATE_URL)) {
                continue;
            }

            $parts = parse_url($text);

            foreach ($this->domains as $domain => $data) {
                if ($parts['host'] !== $domain) {
                    continue;
                }

                if (!preg_match($data['pattern'], $parts['path'], $matches)) {
                    continue;
                }

                if (!isset($data['licenses'][$matches[2]])) {
                    continue;
                }

                $prefix = 'rights_statements_' .
                    str_replace('.', '_', $domain);
                $format = get_option($prefix . '_format');

                if ($format === 'disabled') {
                    continue;
                }

                $rights[$text] = array(
                    'domain' => $domain,
                    'format' => $format,
                    'height' => get_option($prefix . '_height'),
                    'license' => $data['licenses'][$matches[2]],
                    'matches' => $matches
                );
            }
        }

        return $rights;
    }
}
