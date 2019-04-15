<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

final class CH_GA_Data_API {

    /**
     * CH_GA_Data_API Constructor.
     */

    public function __construct() {
        $this->init_hooks();
        do_action( 'ch_ga_data_api_loaded' );
    }


    private function init_hooks() {

        add_action('woocommerce_after_add_to_cart_form', array( $this, 'render_pageview_template'), 30 );

        // AJAX Functions
        add_action( 'wp_ajax_nopriv_get_views_by_page', array( $this,'get_views_by_page') );
        add_action( 'wp_ajax_get_views_by_page', array( $this,'get_views_by_page') );

        // Create Page in Admin
        //add_action('admin_menu', array( $this,'add_admin_menu_item'));

        add_action( 'wp_enqueue_scripts', array( $this,'includes_scripts'), 1000 );

        // add_action( 'admin_enqueue_styles', array( $this,'includes_styles') );
        // add_action( 'admin_print_scripts', array( $this,'includes_scripts') );

    }

    public function get_this() {
        // Load the Google API PHP Client Library.
        require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';

        $analytics = $this->initializeAnalytics();
        $response = $this->getReport($analytics);
        $this->printResults($response);
    }

    public function render_pageview_template() {
      include( dirname( CH_GA_DATA_API_FILE  )  . '/templates/ch-template-ga-pageviews.php');
    }

    public function get_views_by_page() {

        // Get url

        $url = $_POST['url'];

        if ( !$url ) {
          wp_send_json( json_encode( false ) );
          die();
        }

        $url = str_replace('/nl/', "", $url);
        $url = str_replace('/fr/', "", $url);
        // if url does not exist return false

        // Load the Google API PHP Client Library.
        require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';

        // // get data 
        $analytics = $this->initializeAnalytics();
        $response = $this->getPageViewsReport($analytics, $url );

        // return json value.
        //wp_send_json( json_encode( $this->printResults($response) ) );
        wp_send_json( json_encode( $response ) );
    }

    public function includes_scripts() {

        $woo_permalinks = wc_get_permalink_structure();

        wp_register_script( 'ch_ga_data_api',  plugin_dir_url( CH_GA_DATA_API_FILE ) . 'assets/js/ch-ga_data_api.js', array('jquery' ), '1.0.0', true );
        wp_enqueue_script( 'ch_ga_data_api' );

        wp_localize_script( 'ch_ga_data_api', 'ch_ga_data_api', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'text' => array( __("people are viewing this product. Buy now before it's gone.",'crowdyhouse') ),
            'shopBase' => $woo_permalinks['product_base']
        ));
    }

    public function includes_styles() {
        // wp_register_style('bootstrap_css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css');
        // wp_enqueue_style('bootstrap_css');
    }

    /**
     * Initializes an Analytics Reporting API V4 service object.
     *
     * @return An authorized Analytics Reporting API V4 service object.
     */
    public function initializeAnalytics()
    {

      // Use the developers console and download your service account
      // credentials in JSON format. Place them in this directory or
      // change the key file location if necessary.
      $KEY_FILE_LOCATION = __DIR__ . '/crowdyhouse-ga-2b21bbe9afeb.json';

      // Create and configure a new client object.
      $client = new Google_Client();
      $client->setApplicationName("Hello Analytics Reporting");
      $client->setAuthConfig($KEY_FILE_LOCATION);
      $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
      $analytics = new Google_Service_AnalyticsReporting($client);

      return $analytics;
    }

        /**
     * Queries the Analytics Reporting API V4.
     *
     * @param service An authorized Analytics Reporting API V4 service object.
     * @return The Analytics Reporting API V4 response.
     */
     public function getSessionsReport($analytics) {

      // Replace with your view ID, for example XXXX.
      $VIEW_ID = "78514355";

      // Create the DateRange object.
      $dateRange = new Google_Service_AnalyticsReporting_DateRange();
      $dateRange->setStartDate("7daysAgo");
      $dateRange->setEndDate("today");

      // Create the Metrics object.
      $sessions = new Google_Service_AnalyticsReporting_Metric();
      $sessions->setExpression("ga:sessions");
      $sessions->setAlias("sessions");

      // Create the ReportRequest object.
      $request = new Google_Service_AnalyticsReporting_ReportRequest();
      $request->setViewId($VIEW_ID);
      $request->setDateRanges($dateRange);
      $request->setMetrics(array($sessions));

      $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
      $body->setReportRequests( array( $request) );
      return $analytics->reports->batchGet( $body );
    }

    /**
     * Queries the Analytics Reporting API V4.
     *
     * @param service An authorized Analytics Reporting API V4 service object.
     * @return The Analytics Reporting API V4 response.
     */
     public function getPageViewsReport($analytics, $url) {

      // Replace with your view ID, for example XXXX.
      $VIEW_ID = "78514355";

      // Create the DateRange object.
      $dateRange = new Google_Service_AnalyticsReporting_DateRange();
      $dateRange->setStartDate("30daysAgo");
      $dateRange->setEndDate("today");

      // Create the Metrics object.
      $pageviews = new Google_Service_AnalyticsReporting_Metric();
      $pageviews->setExpression("ga:uniquePageviews");
      $pageviews->setAlias("pageviews");

      //Create the Dimensions object.
      $pagepath = new Google_Service_AnalyticsReporting_Dimension();
      $pagepath->setName("ga:pagePath");

      // create the metric filter
      $dimensionFilterSessions = new Google_Service_AnalyticsReporting_DimensionFilter();
      $dimensionFilterSessions->setDimensionName("ga:pagePath");
      $dimensionFilterSessions->setExpressions( $url );
      $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
      $dimensionFilterClause->setFilters([$dimensionFilterSessions]);

      // Create the ReportRequest object.
      $request = new Google_Service_AnalyticsReporting_ReportRequest();
      $request->setViewId($VIEW_ID);
      $request->setDateRanges($dateRange);
      // $request->setSegments(array($segment));
      $request->setDimensionFilterClauses([$dimensionFilterClause]);
      $request->setDimensions(array($pagepath));
      $request->setMetrics(array($pageviews));

      $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
      $body->setReportRequests( array( $request) );
      return $analytics->reports->batchGet( $body );
    }


    /**
     * Parses and prints the Analytics Reporting API V4 response.
     *
     * @param An Analytics Reporting API V4 response.
     */
    public function printResults($reports) {
      for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
        $report = $reports[ $reportIndex ];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();

        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
          $row = $rows[ $rowIndex ];
          $dimensions = $row->getDimensions();
          $metrics = $row->getMetrics();
          for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
            print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
          }

          for ($j = 0; $j < count($metrics); $j++) {
            $values = $metrics[$j]->getValues();
            for ($k = 0; $k < count($values); $k++) {
              $entry = $metricHeaders[$k];
              // print($entry->getName() . ": " . $values[$k] . "\n");
              return array($entry->getName() => $values[$k]);
            }
          }
        }
      }
    }




}