<?php
/**
 * Omeka Rights Statements Plugin
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2018 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Rights Statements Plugin: Plugin Class
 *
 * @package RightsStatements
 */
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
        'rights_statements_preference' => '',
        'rights_statements_target' => ''
    );

    /**
     * Domains with rights statements and accompanying details.
     * @var array
     */
    private $domains = array(
        // Rights Statements
        'rightsstatements.org' => array(
            // Domain and pattern matches these examples:
            // http://rightsstatements.org/page/CNE/1.0/
            // http://rightsstatements.org/vocab/CNE/1.0/
            'pattern' => '{^/(page|vocab)/([A-Z-]+)/([\d.]+)/?$}i',

            // See http://rightsstatements.org/page/1.0/
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

            // See http://rightsstatements.org/en/documentation/assets.html
            'formats' => array(
                'dark-white-interior-blue-type' => 50,
                'dark-white-interior' => 50,
                'dark' => 50,
                'white' => 50
            )
        ),

        // Creative Commons
        'creativecommons.org' => array(
            // Domain and pattern matches these examples:
            // https://creativecommons.org/licenses/by/4.0/
            // https://creativecommons.org/publicdomain/mark/1.0/
            'pattern' => '{^/(licenses|publicdomain)/([A-Z-]+)/([\d.]+)/?$}i',

            // See https://creativecommons.org/licenses/
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

            // See https://licensebuttons.net/
            'formats' => array(
                '88x31' => 31,
                '80x15' => 15
            )
        )
    );

    /**
     * Plugin constructor.
     *
     * Defines options for domains, and calls parent constructor.
     */
    public function __construct()
    {
        // Setup height and format options for each domain.
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

        if (version_compare($args['old_version'], '1.2', '<=')) {
            set_option('rights_statements_target', '');
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

    /**
     * Hook into admin item browse to display rights in details.
     * @param array $args Arguments to the hook.
     */
    public function hookAdminItemsBrowseDetailedEach($args)
    {
        // Obtain the name of all licenses applied to the item.
        $output = array();

        foreach ($this->getRights($args['item']) as $right) {
            $output[] = $right['license'];
        }

        // If there are licenses, append them to the details.
        if ($output) {
            echo '<div class="rights-statements"><strong>Rights:</strong> ';
            echo implode(', ', $output);
            echo '</div>';
        }
    }

    /**
     * Filter public display of DC Rights to link image of rights statement.
     * @param string $text Text of the rights statement to filter.
     * @param array $args Arguments to the filter.
     * @return string Text to display for the rights statement.
     */
    public function rightsStatement($text, $args)
    {
        // Do not display linked image in the admin interface.
        if (is_admin_theme()) {
            return $text;
        }

        // Obtain all rights statements applied to the record.
        $rights = $this->getRights($args['record']);

        // If the current DC Rights text is not a statement, return as-is.
        if (!isset($rights[$text])) {
            return $text;
        }

        // If a preferred domain was specified, check that the current text
        // is from that domain, or that domain is not specified in other text.
        $pref = get_option('rights_statements_preference');

        if ($pref) {
            foreach ($rights as $right) {
                if ($right['domain'] === $pref && $right !== $rights[$text]) {
                    return '';
                }
            }
        }

        // Check if the link should target a new window.
        $target = get_option('rights_statements_target');

        // Return a linked image for the rights statement.
        return
            '<p class="rights-statements">' .
            '<a href="http://'.
                $rights[$text]['domain'] . '/' .
                $rights[$text]['matches'][1] . '/' .
                $rights[$text]['matches'][2] . '/' .
                $rights[$text]['matches'][3] . '/"' .
                ($target ? ' target="_blank"' : '') . '>' .
            '<img src="' . web_path_to(
                $rights[$text]['domain'] . '/' .
                $rights[$text]['format'] . '/' .
                $rights[$text]['matches'][2] . '.svg'
            ). '" alt="' .
                $rights[$text]['license'] . '" height="' .
                $rights[$text]['height'] . '"></a></p>';
    }

    /**
     * Get all rights statements for an record.
     * @param Object $record The record to retrieve rights statements from.
     * @return array Details of the rights statements for the record.
     */
    private function getRights($record)
    {
        $rights = array();

        // Retrieve all of the DC Rights texts from the record.
        $texts = metadata(
            $record,
            array('Dublin Core', 'Rights'),
            array('all' => true, 'no_escape' => true, 'no_filter' => true)
        );

        foreach ($texts as $text) {
            // Only process texts that are valid URLs.
            if (!filter_var($text, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Parse the URL into parts.
            $parts = parse_url($text);

            foreach ($this->domains as $domain => $data) {
                // Only parse domains that match the URL.
                if ($parts['host'] !== $domain) {
                    continue;
                }

                // Only parse paths that match the patter.
                if (!preg_match($data['pattern'], $parts['path'], $matches)) {
                    continue;
                }

                // Only pass URLs for known licenses.
                if (!isset($data['licenses'][$matches[2]])) {
                    continue;
                }

                // Get the format for the statement.
                $prefix = 'rights_statements_' .
                    str_replace('.', '_', $domain);
                $format = get_option($prefix . '_format');

                // If the format is disabled, do not process the statement.
                if ($format === 'disabled') {
                    continue;
                }

                // Add the statement to the result.
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
