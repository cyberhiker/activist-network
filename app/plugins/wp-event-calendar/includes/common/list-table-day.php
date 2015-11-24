<?php

/**
 * Calendar Day List Table
 *
 * @package Calendar/ListTables/Day
 *
 * @see WP_Posts_List_Table
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Calendar Day List Table
 *
 * This list table is responsible for showing events in a traditional table,
 * even though it extends the `WP_List_Table` class. Tables & lists & tables.
 */
class WP_Event_Calendar_Day_Table extends WP_Event_Calendar_List_Table {

	/**
	 * The main constructor method
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	/**
	 * Setup the list-table's columns
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @return array An associative array containing column information
	 */
	public function get_columns() {
		return array(
			'sunday'    => $GLOBALS['wp_locale']->get_weekday( 0 ),
			'monday'    => $GLOBALS['wp_locale']->get_weekday( 1 ),
			'tuesday'   => $GLOBALS['wp_locale']->get_weekday( 2 ),
			'wednesday' => $GLOBALS['wp_locale']->get_weekday( 3 ),
			'thursday'  => $GLOBALS['wp_locale']->get_weekday( 4 ),
			'friday'    => $GLOBALS['wp_locale']->get_weekday( 5 ),
			'saturday'  => $GLOBALS['wp_locale']->get_weekday( 6 )
		);
	}

	/**
	 * Get a list of CSS classes for the list table table tag.
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'calendar', $this->_args['plural'] );
	}

	/**
	 * Prepare the list-table items for display
	 *
	 * @since 0.1.0
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_orderby()
	 * @uses $this->get_order()
	 */
	public function prepare_items() {

		// Set column headers
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			array()
		);

		// Handle bulk actions
		$this->process_bulk_action();

		// Query for posts for this month only
		$this->query = new WP_Query( $this->filter_month_args() );

		// Max per day
		$max_per_day = $this->get_per_day();

		// Rearrange posts into an array keyed by day of the month
		foreach ( $this->query->posts as $post ) {

			// Get start & end
			$this->_all_day = get_post_meta( $post->ID, 'wp_event_calendar_all_day',       true );
			$this->item_start   = get_post_meta( $post->ID, 'wp_event_calendar_date_time',     true );
			$this->item_end     = get_post_meta( $post->ID, 'wp_event_calendar_end_date_time', true );

			// Format start
			if ( ! empty( $this->item_start ) ) {
				$this->item_start = strtotime( $this->item_start );
			}

			// Format end
			if ( ! empty( $this->item_end ) ) {
				$this->item_end = strtotime( $this->item_end );
			}

			// Prepare pointer & item
			$this->setup_item( $post, $max_per_day );
		}
	}

	/**
	 * Return filtered query arguments
	 *
	 * @since 0.1.1
	 *
	 * @return array
	 */
	private function filter_month_args() {

		// Events
		if ( 'event' === $this->screen->post_type ) {
			$args = array(
				'post_type'           => $this->screen->post_type,
				'post_status'         => $this->get_post_status(),
				'posts_per_page'      => -1,
				'orderby'             => 'meta_value',
				'order'               => $this->get_order(),
				'hierarchical'        => false,
				'ignore_sticky_posts' => true,
				's'                   => $this->get_search(),
				'meta_query'          => array(
					array(
						'key'     => 'wp_event_calendar_date_time',
						'value'   => array( "{$this->year}-{$this->month}-01 00:00:00","{$this->year}-{$this->month}-31 00:00:00" ),
						'type'    => 'DATETIME',
						'compare' => 'BETWEEN',
					)
				)
			);

		// All others
		} else {
			$args = array(
				'post_type'           => $this->screen->post_type,
				'post_status'         => $this->get_post_status(),
				'monthnum'            => $this->month,
				'year'                => $this->year,
				'day'                 => null,
				'posts_per_page'      => -1,
				'orderby'             => $this->get_orderby(),
				'order'               => $this->get_order(),
				'hierarchical'        => false,
				'ignore_sticky_posts' => true,
				's'                   => $this->get_search()
			);
		}

		return apply_filters( 'wp_event_calendar_month_query', $args );
	}

	/**
	 * Paginate through months & years
	 *
	 * @since 0.1.0
	 *
	 * @param array $args
	 */
	protected function pagination( $args = array() ) {

		// Parse args
		$args = wp_parse_args( $args, array(
			'small'  => '1 day',
			'large'  => '1 week',
			'labels' => array(
				'next_small' => esc_html__( 'Tomorrow',  'wp-event-calendar' ),
				'next_large' => esc_html__( 'Next Week', 'wp-event-calendar' ),
				'prev_small' => esc_html__( 'Yesterday', 'wp-event-calendar' ),
				'prev_large' => esc_html__( 'Last Week', 'wp-event-calendar' )
			)
		) );

		// Return pagination
		return parent::pagination( $args );
	}

	/**
	 * Output month & year inputs, for viewing relevant posts
	 *
	 * @since 0.1.0
	 *
	 * @param  string  $which
	 */
	protected function extra_tablenav( $which = '' ) {

		// No bottom extras
		if ( 'top' !== $which ) {
			return;
		}

		// Start an output buffer
		ob_start(); ?>

		<label for="month" class="screen-reader-text"><?php esc_html_e( 'Switch to this month', 'wp-event-calendar' ); ?></label>
		<select name="month" id="month">

			<?php for ( $month_index = 1; $month_index <= 12; $month_index++ ) : ?>

				<option value="<?php echo esc_attr( $month_index ); ?>" <?php selected( $month_index, $this->month ); ?>><?php echo $GLOBALS['wp_locale']->get_month( $month_index ); ?></option>

			<?php endfor;?>

		</select>

		<label for="year" class="screen-reader-text"><?php esc_html_e( 'Switch to this year', 'wp-event-calendar' ); ?></label>
		<input type="number" name="year" id="year" value="<?php echo (int) $this->year; ?>" size="5">

		<?php

		// Allow additional tablenav output before the "View" button
		do_action( 'wp_event_calendar_before_tablenav_view' );

		// Output the "View" button
		submit_button( esc_html__( 'View', 'wp-event-calendar' ), 'action', '', false, array( 'id' => "doaction" ) );

		// Filter & return
		return apply_filters( 'wp_event_calendar_get_extra_tablenav', ob_get_clean() );
	}

	/**
	 * Start the week with a table row
	 *
	 * @since 0.1.0
	 */
	protected function get_week_start() {
		?>

			<tr>

		<?php
	}

	/**
	 * End the week with a closed table row
	 *
	 * @since 0.1.0
	 */
	protected function get_week_end() {
		?>

			</tr>

		<?php
	}

	/**
	 * Start the week with a table row
	 *
	 * @since 0.1.0
	 */
	protected function get_week_day_pad(  $iterator = 1, $start_day = 1 ) {
		?>

			<th class="padding <?php echo $this->get_day_classes( $iterator, $start_day ); ?>"></th>

		<?php
	}

	/**
	 * Start the week with a table row
	 *
	 * @since 0.1.0
	 */
	protected function get_week_day( $iterator = 1, $start_day = 1 ) {

		// Calculate the day of the month
		$day_of_month = (int) ( $iterator - (int) $start_day + 1 ) ?>

		<td class="<?php echo $this->get_day_classes( $iterator, $start_day ); ?>">
			<span class="day-number">
				<?php echo (int) $day_of_month; ?>
			</span>

			<div class="events-for-day">
				<?php echo $this->get_day_posts( $day_of_month ); ?>
			</div>
		</td>

		<?php
	}

	/**
	 * Get the already queried posts for a given day
	 *
	 * @since 0.1.0
	 *
	 * @param int $day
	 *
	 * @return array
	 */
	protected function get_day_queried_posts( $day = 1 ) {
		return isset( $this->items[ $day ] )
			? $this->items[ $day ]
			: array();
	}

	/**
	 * Get posts for the day
	 *
	 * @since 0.1.0
	 *
	 * @param int $day
	 *
	 * @return string
	 */
	protected function get_day_posts( $day = 1 ) {

		// Get posts and bail if none
		$posts = $this->get_day_queried_posts( $day );
		if ( empty( $posts ) ) {
			return '';
		}

		// Start an output buffer
		ob_start();

		// Loop through today's posts
		foreach ( $posts as $post ) :

			// Setup the pointer ID
			$ponter_id = "{$post->ID}-{$day}";

			// Get the post link
			$post_link = get_edit_post_link( $post->ID );

			// Handle empty titles
			$post_title = get_the_title( $post->ID );
			if ( empty( $post_title ) ) {
				$post_title = esc_html__( '(No title)', 'wp-event-calendar' );
			} ?>

			<a id="event-pointer-<?php echo esc_attr( $ponter_id ); ?>" href="<?php echo esc_url( $post_link ); ?>" class="<?php echo $this->get_day_post_classes( $post->ID ); ?>"><?php echo esc_html( $post_title ); ?></a>

		<?php endforeach;

		return ob_get_clean();
	}

	/**
	 * Get classes for post in day
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id
	 */
	protected function get_day_post_classes( $post_id = 0 ) {
		return join( ' ', get_post_class( '', $post_id ) );
	}

	/**
	 * Is the current calendar view today
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	protected function is_today( $month, $day, $year ) {
		$_month = (bool) ( $month == date_i18n( 'n' ) );
		$_day   = (bool) ( $day   == date_i18n( 'j' ) );
		$_year  = (bool) ( $year  == date_i18n( 'Y' ) );

		return (bool) ( true === $_month && true === $_day && true === $_year );
	}

	/**
	 * Get classes for table cell
	 *
	 * @since 0.1.0
	 *
	 * @param   type  $iterator
	 * @param   type  $start_day
	 *
	 * @return  type
	 */
	protected function get_day_classes( $iterator = 1, $start_day = 1 ) {
		$dow      = ( $iterator % 7 );
		$day_key  = sanitize_key( $GLOBALS['wp_locale']->get_weekday( $dow ) );

		$offset   = ( $iterator - $start_day ) + 1;

		// Position & day info
		$position     = "position-{$dow}";
		$day_number   = "day-{$offset}";
		$month_number = "month-{$this->month}";
		$year_number  = "year-{$this->year}";

		$is_today = $this->is_today( $this->month, $offset, $this->year )
			? 'today'
			: '';

		// Assemble classes
		$classes = array(
			$day_key,
			$is_today,
			$position,
			$day_number,
			$month_number,
			$year_number
		);

		return implode( ' ', $classes );
	}

	/**
	 * Display a calendar by month and year
	 *
	 * @since 0.1.0
	 *
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 */
	protected function display_mode( $year = 2015, $month = 1, $day = 1 ) {

		// Get timestamp
		$timestamp  = mktime( 0, 0, 0, $month, 1, $year );
		$max_day    = date_i18n( 't', $timestamp );
		$this_month = getdate( $timestamp );
		$start_day  = $this_month['wday'];

		// Loop through days of the month
		for ( $i = 0; $i < ( $max_day + $start_day ); $i++ ) {

			// New row
			if ( ( $i % 7 ) === 0  ) {
				$this->get_week_start();
			}

			// Pad day
			if ( $i < $start_day ) {
				$this->get_week_day_pad( $i, $start_day );

			// Month day
			} else {
				$this->get_week_day( $i, $start_day );
			}

			if ( ( $i % 7 ) === 6 ) {
				$this->get_week_end();
			}
		}
	}
}
