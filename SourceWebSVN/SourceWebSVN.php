<?php
# Copyright (C) 2008	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

class SourceWebSVNPlugin extends MantisSourcePlugin {
	function register() {
		$this->name = lang_get( 'plugin_SourceWebSVN_title' );
		$this->description = lang_get( 'plugin_SourceWebSVN_description' );

		$this->version = '0.9a';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.9a',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
	}

	function get_types( $p_event ) {
		return array( 'svn' => lang_get( 'plugin_SourceWebSVN_svn' ) );
	}

	function show_type( $p_event, $p_type ) {
		if ( 'svn' == strtolower( $p_type ) ) {
			return lang_get( 'plugin_SourceWebSVN_svn' );
		}
	}

	function show_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'svn' == strtolower($p_repo->type ) ) {
			return "$p_repo->name $p_changeset->branch r$p_changeset->revision";
		}
	}

	function show_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'svn' == strtolower($p_repo->type ) ) {
			return $p_file->action . ' - ' . $p_file->filename;
		}
	}

	function url_repo( $p_event, $p_repo, $p_changeset=null ) {
		if ( 'svn' == strtolower($p_repo->type ) ) {
			if ( !is_null( $p_changeset ) ) {
				$t_rev = '&rev=' . urlencode( $p_changeset->revision );
			}
			if ( !is_blank( $p_repo->info['websvn_path'] ) ) {
				$t_path = '&path=' . urlencode( $p_repo->info['websvn_path'] );
			}
			return $p_repo->info['websvn_url'] . 'listing.php?repname=' . urlencode( $p_repo->info['websvn_name'] ) . "$t_path$t_rev&sc=1";
		}
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'svn' == strtolower($p_repo->type ) ) {
			return $this->url_repo( $p_event, $p_repo, $p_changeset );
		}
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'svn' == strtolower($p_repo->type ) ) {
			if ( $p_file->action == 'D' ) {
				return '';
			}
			return $p_repo->info['websvn_url'] . 'filedetails.php?repname=' . urlencode( $p_repo->info['websvn_name'] ) .
				'&rev=' . urlencode( $p_changeset->revision ) . '&path=' . urlencode( $p_file->filename ) . '&sc=1';
		}
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'svn' == strtolower($p_repo->type ) ) {
			if ( $p_file->action == 'D' || $p_file->action == 'A' ) {
				return '';
			}
			return $p_repo->info['websvn_url'] . 'diff.php?repname=' . urlencode( $p_repo->info['websvn_name'] ) .
				'&rev=' . urlencode( $p_changeset->revision ) . '&path=' . urlencode( $p_file->filename ) . '&sc=1';
		}
	}

	function update_repo_form( $p_event, $p_repo ) {
		if ( 'svn' != strtolower( $p_repo->type ) ) {
			return $p_repo;
		}

		$t_url = $t_name = '';
		if ( isset( $p_repo->info['websvn_url'] ) ) {
			$t_url = $p_repo->info['websvn_url'];
		}
		if ( isset( $p_repo->info['websvn_name'] ) ) {
			$t_name = $p_repo->info['websvn_name'];
		}
		if ( isset( $p_repo->info['websvn_path'] ) ) {
			$t_path = $p_repo->info['websvn_path'];
		}
		if ( isset( $p_repo->info['standard_repo'] ) ) {
			$t_branches = $p_repo->info['standard_repo'];
		}
?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_url' ) ?></td>
<td><input name="websvn_url" maxlength="250" size="40" value="<?php echo string_attribute( $t_url ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_name' ) ?></td>
<td><input name="websvn_name" maxlength="250" size="40" value="<?php echo string_attribute( $t_name ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_path' ) ?></td>
<td><input name="websvn_path" maxlength="250" size="40" value="<?php echo string_attribute( $t_path ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_standard_repo' ) ?></td>
<td><input name="standard_repo" type="checkbox" <?php echo ($t_branches ? 'checked="checked"' : '') ?>/></td>
</tr>
<?php
	}

	function update_repo( $p_event, $p_repo ) {
		$f_websvn_url = gpc_get_string( 'websvn_url' );
		$f_websvn_name = gpc_get_string( 'websvn_name' );
		$f_websvn_path = gpc_get_string( 'websvn_path' );
		$f_standard_repo = gpc_get_bool( 'standard_repo', false );

		$p_repo->info['websvn_url'] = $f_websvn_url;
		$p_repo->info['websvn_name'] = $f_websvn_name;
		$p_repo->info['websvn_path'] = $f_websvn_path;
		$p_repo->info['standard_repo'] = $f_standard_repo;

		return $p_repo;
	}

	function commit( $p_event, $p_repo, $p_data ) {
		if ( 'svn' != strtolower($p_repo->type ) ) {
			return null;
		}

		if ( preg_match( '/(\d+)/', $p_data, $p_matches ) ) {
			$t_url = $p_repo->url;
			$t_revision = $p_matches[1];
			$t_svnlog = explode( "\n", `svn log -v $t_url -r$t_revision` );

			if ( SourceChangeset::exists( $p_repo->id, $t_revision ) ) {
				echo "Revision $t_revision already committed!\n";
				return null;
			}

			return $this->process_svn_log( $p_repo, $t_svnlog );
		}

	}

	function import_repo( $p_event, $p_repo ) {
		if ( 'svn' != strtolower( $p_repo->type ) ) {
			return $p_repo;
		}

		$this->check_svn();

		$t_url = $p_repo->url;
		$t_svnlog = explode( "\n", `svn log -v $t_url` );

		$this->process_svn_log( $p_repo, $t_svnlog, false );

		return true;
	}

	function check_svn() {
		if ( is_blank( `svn help` ) ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}
	}

	function process_svn_log( $p_repo, $p_svnlog, $p_return_sets=true ) {
		$t_state = 0;
		$t_svnline = str_pad( '', 72, '-' );

		$t_changesets = array();
		$t_changeset = null;
		$t_comments = '';
		$t_count = 0;

		foreach( $p_svnlog as $t_line ) {

			# starting state, do nothing
			if ( 0 == $t_state ) {
				if ( $t_line == $t_svnline ) {
					$t_state = 1;
				}

			# Changeset info
			} elseif ( 1 == $t_state && preg_match( '/^r([0-9]+) \| (\w+) \| ([0-9\-]+) ([0-9:]+)/', $t_line, $t_matches ) ) {
				if ( !is_null( $t_changeset ) ) {
					if ( $p_return_sets ) {
						$t_changesets[] = $t_changeset;
					} else {
						$t_changeset->bugs = Source_Parse_Buglinks( $t_changeset->message );
						$t_changeset->save();
					}
				}

				$t_user_id = user_get_id_by_name( $t_matches[2] );
				$t_user_id = ( false === $t_user_id ? 0 : $t_user_id );

				$t_changeset = new SourceChangeset( $p_repo->id, $t_matches[1], '', $t_matches[3] . ' ' . $t_matches[4], $t_matches[2], '', $t_user_id );

				$t_state = 2;

			# Changed paths
			} elseif ( 2 == $t_state ) {
				if ( strlen( $t_line ) == 0 ) {
					$t_state = 3;
				} else {
					if ( preg_match( '/^\s+([a-zA-Z])\s+([\S]+)/', $t_line, $t_matches ) ) {
						$t_file = new SourceFile( $t_changeset->id, '', $t_matches[2], $t_matches[1] );
						$t_changeset->files[] = $t_file;

						# Branch-checking
						if ( is_blank( $t_changeset->branch) ) {
							# Look for standard trunk/branches/tags information
							if ( $p_repo->info['standard_repo'] ) {
								if ( preg_match( '/\/(?:(trunk)|(?:branches|tags)\/([^\/]+))/', $t_file->filename, $t_matches ) ) {
									if ( 'trunk' == $t_matches[1] ) {
										$t_changeset->branch = 'trunk';
									} else {
										$t_changeset->branch = $t_matches[2];
									}
								}
							} else {
								if ( preg_match( '/\/([^\/]+))/', $t_file->filename, $t_matches ) ) {
									$t_changeset->branch = $t_matches[1];
								}
							}
						}
					}
				}

			# Changeset comments
			} elseif ( 3 == $t_state ) {
				if ( $t_line == $t_svnline ) {
					$t_state = 1;
				} else {
					$t_changeset->message .= $t_line . "\n"; 
				}

			# Should only happen at the end...
			} else {
				break;
			}
		}

		if ( !is_null( $t_changeset ) ) {
			if ( $p_return_sets ) {
				$t_changesets[] = $t_changeset;
			} else {
				$t_changeset->bugs = Source_Parse_Buglinks( $t_changeset->message );
				$t_changeset->save();
			}
		}

		return $t_changesets;
	}
}