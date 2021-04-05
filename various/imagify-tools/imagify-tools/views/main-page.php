<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>

<table class="widefat fixed striped">
	<thead>
		<tr>
			<th scope="col"><?php _e( 'Test', 'imagify-tools' ); ?></th>
			<th scope="col"><?php _e( 'Current value', 'imagify-tools' ); ?></th>
			<th scope="col"><?php _e( 'More info', 'imagify-tools' ); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th scope="col"><?php _e( 'Test', 'imagify-tools' ); ?></th>
			<th scope="col"><?php _e( 'Current value', 'imagify-tools' ); ?></th>
			<th scope="col"><?php _e( 'More info', 'imagify-tools' ); ?></th>
		</tr>
	</tfoot>
	<tbody>
		<?php
		foreach ( $this->model->get_data() as $group_name => $group ) {
			?>
			<tr class="row-group-title" scope="col">
				<th colspan="3"><?php echo $group_name; ?></th>
			</tr>
			<?php
			foreach ( $group as $test ) {
				if ( isset( $test['compare'] ) ) {
					$is_error = $test['value'] !== $test['compare'];
				} elseif ( isset( $test['is_error'] ) ) {
					$is_error = $test['is_error'];
				} else {
					$is_error = false;
				}

				if ( is_bool( $test['value'] ) ) {
					$value = true === $test['value'] ? __( 'Yes', 'imagify-tools' ) : __( 'No', 'imagify-tools' );
				} elseif ( $test['value'] && is_array( $test['value'] ) && ! is_numeric( key( $test['value'] ) ) ) {
					$value = array();

					foreach ( $test['value'] as $k => $v ) {
						$kv = esc_html( $k ) . '</th><td>';

						if ( $k && is_array( $v ) && is_numeric( key( $v ) ) ) {
								$kv .= '<pre>' . esc_html( implode( ', ', $v ) ) . '</pre>';
						} else {
							$kv .= '<pre>' . esc_html( call_user_func( 'print_r', $v, 1 ) ) . '</pre>';
						}

						$value[] = $kv;
					}

					sort( $value );

					$value = '<table><tbody><tr><th>' . implode( "</td></tr>\n<tr><th>", $value ) . '</td></tr></tbody></table>';
				} else {
					$value = '<pre>' . esc_html( call_user_func( 'print_r', $test['value'], 1 ) ) . '</pre>';
				}

				$more_info = isset( $test['more_info'] ) ? $test['more_info'] : '';
				?>
				<tr<?php echo $is_error ? ' class="row-error"' : ''; ?>>
					<th scope="row"><?php echo $test['label']; ?></th>
					<td><?php echo $value; ?></td>
					<td><?php echo $more_info; ?></td>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>
