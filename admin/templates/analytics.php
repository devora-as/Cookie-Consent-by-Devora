<?php

/**
 * Admin Analytics Template
 *
 * Analytics dashboard for the cookie consent plugin.
 *
 * @package CustomCookieConsent
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get statistics data
$consent_logger = new \CustomCookieConsent\ConsentLogger();
$period         = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : 'month';
$stats          = $consent_logger->get_consent_statistics( $period );

// Get logs data
$page      = isset( $_GET['log_page'] ) ? intval( $_GET['log_page'] ) : 1;
$logs_data = $consent_logger->get_consent_logs( $page );
?>

<div class="wrap cookie-consent-admin-wrap">
	<div class="cookie-consent-admin-header">
		<h1><?php esc_html_e( 'Analytics & Statistics', 'custom-cookie-consent' ); ?></h1>

		<div class="cookie-consent-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=export_consent_logs&period=' . $period . '&_wpnonce=' . wp_create_nonce( 'export_consent_logs' ) ) ); ?>" class="button">
				<?php esc_html_e( 'Export CSV', 'custom-cookie-consent' ); ?>
			</a>
		</div>
	</div>

	<div class="cookie-consent-admin-nav">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-cookie-consent' ) ); ?>">
			<?php esc_html_e( 'Dashboard', 'custom-cookie-consent' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-cookie-scanner' ) ); ?>">
			<?php esc_html_e( 'Cookie Scanner', 'custom-cookie-consent' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-cookie-settings' ) ); ?>">
			<?php esc_html_e( 'Settings', 'custom-cookie-consent' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-cookie-translations' ) ); ?>">
			<?php esc_html_e( 'Text & Translations', 'custom-cookie-consent' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-cookie-analytics' ) ); ?>" class="active">
			<?php esc_html_e( 'Analytics & Statistics', 'custom-cookie-consent' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-cookie-documentation' ) ); ?>">
			<?php esc_html_e( 'Documentation', 'custom-cookie-consent' ); ?>
		</a>
	</div>

	<div class="cookie-consent-admin-card">
		<h2><?php esc_html_e( 'Consent Statistics', 'custom-cookie-consent' ); ?></h2>

		<div class="consent-analytics-period">
			<label for="statistics-period"><?php esc_html_e( 'Time Period:', 'custom-cookie-consent' ); ?></label>
			<select id="statistics-period" class="js-statistics-period">
				<option value="day" <?php selected( $period, 'day' ); ?>><?php esc_html_e( 'Last 24 Hours', 'custom-cookie-consent' ); ?></option>
				<option value="week" <?php selected( $period, 'week' ); ?>><?php esc_html_e( 'Last 7 Days', 'custom-cookie-consent' ); ?></option>
				<option value="month" <?php selected( $period, 'month' ); ?>><?php esc_html_e( 'Last 30 Days', 'custom-cookie-consent' ); ?></option>
				<option value="year" <?php selected( $period, 'year' ); ?>><?php esc_html_e( 'Last 12 Months', 'custom-cookie-consent' ); ?></option>
				<option value="all" <?php selected( $period, 'all' ); ?>><?php esc_html_e( 'All Time', 'custom-cookie-consent' ); ?></option>
			</select>
		</div>

		<div class="consent-analytics-overview">
			<div class="analytics-card total-consents">
				<h3><?php esc_html_e( 'Total Consent Actions', 'custom-cookie-consent' ); ?></h3>
				<div class="analytics-number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></div>
			</div>

			<div class="analytics-card-group">
				<div class="analytics-card consent-category necessary">
					<h3><?php esc_html_e( 'Necessary', 'custom-cookie-consent' ); ?></h3>
					<div class="analytics-percentage">
						<div class="percentage-value"><?php echo esc_html( $stats['percentage']['necessary'] ); ?>%</div>
						<div class="percentage-bar">
							<div class="percentage-fill" style="width: <?php echo esc_attr( $stats['percentage']['necessary'] ); ?>%;"></div>
						</div>
					</div>
					<div class="analytics-count"><?php echo esc_html( number_format_i18n( $stats['categories']['necessary'] ) ); ?></div>
				</div>

				<div class="analytics-card consent-category analytics">
					<h3><?php esc_html_e( 'Analytics', 'custom-cookie-consent' ); ?></h3>
					<div class="analytics-percentage">
						<div class="percentage-value"><?php echo esc_html( $stats['percentage']['analytics'] ); ?>%</div>
						<div class="percentage-bar">
							<div class="percentage-fill" style="width: <?php echo esc_attr( $stats['percentage']['analytics'] ); ?>%;"></div>
						</div>
					</div>
					<div class="analytics-count"><?php echo esc_html( number_format_i18n( $stats['categories']['analytics'] ) ); ?></div>
				</div>

				<div class="analytics-card consent-category functional">
					<h3><?php esc_html_e( 'Functional', 'custom-cookie-consent' ); ?></h3>
					<div class="analytics-percentage">
						<div class="percentage-value"><?php echo esc_html( $stats['percentage']['functional'] ); ?>%</div>
						<div class="percentage-bar">
							<div class="percentage-fill" style="width: <?php echo esc_attr( $stats['percentage']['functional'] ); ?>%;"></div>
						</div>
					</div>
					<div class="analytics-count"><?php echo esc_html( number_format_i18n( $stats['categories']['functional'] ) ); ?></div>
				</div>

				<div class="analytics-card consent-category marketing">
					<h3><?php esc_html_e( 'Marketing', 'custom-cookie-consent' ); ?></h3>
					<div class="analytics-percentage">
						<div class="percentage-value"><?php echo esc_html( $stats['percentage']['marketing'] ); ?>%</div>
						<div class="percentage-bar">
							<div class="percentage-fill" style="width: <?php echo esc_attr( $stats['percentage']['marketing'] ); ?>%;"></div>
						</div>
					</div>
					<div class="analytics-count"><?php echo esc_html( number_format_i18n( $stats['categories']['marketing'] ) ); ?></div>
				</div>
			</div>
		</div>

		<div class="consent-analytics-chart">
			<h3><?php esc_html_e( 'Consent Trend (Last 30 Days)', 'custom-cookie-consent' ); ?></h3>
			<div class="consent-chart-container">
				<canvas id="consentTrendChart" width="800" height="300"></canvas>
			</div>
		</div>
	</div>

	<div class="cookie-consent-admin-card">
		<h2><?php esc_html_e( 'Consent Log', 'custom-cookie-consent' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Detailed log of all consent actions taken by users. All visitor data is anonymized in compliance with GDPR.', 'custom-cookie-consent' ); ?></p>

		<?php if ( empty( $logs_data['logs'] ) ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No consent logs found for the selected period.', 'custom-cookie-consent' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped consent-logs-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date/Time', 'custom-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'User/Visitor', 'custom-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Categories', 'custom-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Version', 'custom-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Source', 'custom-cookie-consent' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs_data['logs'] as $log ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log['consent_timestamp'] ) ) ); ?></td>
							<td>
								<?php if ( ! empty( $log['user_id'] ) ) : ?>
									<?php
									$user = get_userdata( $log['user_id'] );
									echo $user ? esc_html( $user->display_name ) : esc_html__( 'User ID: ', 'custom-cookie-consent' ) . esc_html( $log['user_id'] );
									?>
								<?php else : ?>
									<?php esc_html_e( 'Anonymous', 'custom-cookie-consent' ); ?> (<?php echo esc_html( substr( $log['visitor_id'], 0, 8 ) . '...' ); ?>)
								<?php endif; ?>
							</td>
							<td>
								<?php
								$categories = explode( ',', $log['consent_categories'] );
								foreach ( $categories as $category ) :
									$category = sanitize_html_class( $category );
									?>
									<span class="consent-category-badge <?php echo esc_attr( $category ); ?>"><?php echo esc_html( ucfirst( $category ) ); ?></span>
								<?php endforeach; ?>
							</td>
							<td><?php echo esc_html( $log['consent_version'] ); ?></td>
							<td><?php echo esc_html( ucfirst( $log['consent_source'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $logs_data['pagination']['total_pages'] > 1 ) : ?>
				<div class="consent-logs-pagination">
					<?php
					echo wp_kses(
						paginate_links(
							array(
								'base'      => add_query_arg( 'log_page', '%#%' ),
								'format'    => '',
								'prev_text' => __( '&laquo;', 'custom-cookie-consent' ),
								'next_text' => __( '&raquo;', 'custom-cookie-consent' ),
								'total'     => $logs_data['pagination']['total_pages'],
								'current'   => $logs_data['pagination']['current_page'],
							)
						),
						array(
							'a'    => array(
								'href'  => array(),
								'class' => array(),
							),
							'span' => array(
								'class'        => array(),
								'aria-current' => array(),
							),
						)
					);
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Period selection handler
		const periodSelect = document.querySelector('.js-statistics-period');
		if (periodSelect) {
			periodSelect.addEventListener('change', function() {
				window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=custom-cookie-analytics&period=' ) ); ?>' + this.value;
			});
		}

		// Chart JS for consent trend
		const ctx = document.getElementById('consentTrendChart').getContext('2d');

		// Prepare data for the chart
		const dates = <?php echo wp_json_encode( array_keys( $stats['trend'] ) ); ?>;
		const counts = <?php echo wp_json_encode( array_values( $stats['trend'] ) ); ?>;

		// Create the chart
		const consentTrendChart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: dates,
				datasets: [{
					label: '<?php echo esc_js( __( 'Consent Actions', 'custom-cookie-consent' ) ); ?>',
					data: counts,
					backgroundColor: 'rgba(54, 162, 235, 0.2)',
					borderColor: 'rgba(54, 162, 235, 1)',
					borderWidth: 2,
					tension: 0.3,
					pointRadius: 3,
					pointBackgroundColor: 'rgba(54, 162, 235, 1)'
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							precision: 0
						}
					},
					x: {
						display: true,
						title: {
							display: true,
							text: '<?php echo esc_js( __( 'Date', 'custom-cookie-consent' ) ); ?>'
						}
					}
				},
				plugins: {
					legend: {
						display: true,
						position: 'top'
					},
					tooltip: {
						callbacks: {
							title: function(tooltipItems) {
								// Format the date for the tooltip
								const date = new Date(tooltipItems[0].label);
								return date.toLocaleDateString();
							}
						}
					}
				}
			}
		});
	});
</script>

<style>
	.consent-analytics-overview {
		display: flex;
		flex-wrap: wrap;
		margin: 20px 0;
		gap: 20px;
	}

	.analytics-card {
		background: #fff;
		border-radius: 5px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		padding: 15px 20px;
		flex: 1;
		min-width: 200px;
	}

	.analytics-card-group {
		display: flex;
		flex-wrap: wrap;
		gap: 20px;
		width: 100%;
	}

	.analytics-card.total-consents {
		background: #f9f9f9;
		flex-basis: 100%;
	}

	.analytics-card h3 {
		margin-top: 0;
		font-size: 14px;
		color: #23282d;
	}

	.analytics-number {
		font-size: 32px;
		font-weight: bold;
		color: #2271b1;
		margin-top: 10px;
	}

	.analytics-percentage {
		display: flex;
		flex-direction: column;
		margin: 10px 0;
	}

	.percentage-value {
		font-size: 24px;
		font-weight: bold;
		margin-bottom: 5px;
	}

	.percentage-bar {
		height: 8px;
		background: #f0f0f0;
		border-radius: 4px;
		overflow: hidden;
	}

	.percentage-fill {
		height: 100%;
		background: #2271b1;
		border-radius: 4px;
	}

	.consent-category.necessary .percentage-fill {
		background: #2271b1;
	}

	.consent-category.analytics .percentage-fill {
		background: #674ea7;
	}

	.consent-category.functional .percentage-fill {
		background: #3d85c6;
	}

	.consent-category.marketing .percentage-fill {
		background: #e69138;
	}

	.consent-analytics-chart {
		margin-top: 30px;
	}

	.consent-chart-container {
		background: #fff;
		border-radius: 5px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		padding: 20px;
		margin-top: 10px;
	}

	.consent-analytics-period {
		display: flex;
		align-items: center;
		margin-bottom: 20px;
	}

	.consent-analytics-period label {
		margin-right: 10px;
		font-weight: bold;
	}

	.consent-category-badge {
		display: inline-block;
		padding: 3px 8px;
		border-radius: 3px;
		font-size: 12px;
		color: #fff;
		margin-right: 5px;
	}

	.consent-category-badge.necessary {
		background: #2271b1;
	}

	.consent-category-badge.analytics {
		background: #674ea7;
	}

	.consent-category-badge.functional {
		background: #3d85c6;
	}

	.consent-category-badge.marketing {
		background: #e69138;
	}

	.consent-logs-pagination {
		margin-top: 20px;
		text-align: center;
	}

	.consent-logs-table {
		margin-top: 15px;
	}
</style>
