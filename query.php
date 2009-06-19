<?php

abstract class CFT_query 
{
	private static $query_vars;
	private static $filters;
	private static $penalties;

	static function init($query_vars)
	{
		self::$query_vars = $query_vars;

		// Set filters		
		self::$filters = array(
			'posts_fields' => '',
			'posts_join' => '',
			'posts_where' => '',
			'posts_groupby' => '',
			'posts_orderby' => '',
			'the_posts' => 'rank_by_order',
		);

		// And add them
		add_action('pre_get_posts', array(__CLASS__, 'add_filters'));
	}

	static function add_filters($obj)
	{
		// Adds filters only to main query
		if ( $GLOBALS['wp_query'] != $obj )
			return;

		foreach ( self::$filters as $name => $callback )
		{
			if ( empty($callback) )
				$callback = $name;
			add_filter($name, array(__CLASS__, $callback));
		}
	}

	static function remove_filters()
	{
		foreach ( self::$filters as $name => $callback )
		{
			if ( empty($callback) )
				$callback = $name;
			remove_filter($name, array(__CLASS__, $callback));
		}
	}

	static function posts_fields($fields)
	{
		global $wpdb;

		$nr = count(self::$query_vars);

		return $fields . ", COUNT(*) * 100 / {$nr} AS meta_rank";
	}

	static function posts_join($join)
	{
		global $wpdb;

		return $join . " JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id)";
	}

	static function posts_where($where)
	{
		global $wpdb;

		if ( is_singular() )
		{
			CFT_core::make_canonical();

			// Get posts instead of front page
			$where = " AND {$wpdb->posts}.post_type = 'post' AND {$wpdb->posts}.post_status = 'publish'";
		}

		// Parse query_vars
		$case = $and = $or = array();
		
		foreach ( self::$query_vars as $key => $value )
		{
			$clause = "WHEN '$key' THEN meta_value ";

			if ( empty($value) )
			{
				$case[] = $clause . "IS NOT NULL";
			}
			elseif ( CFT_core::$options->allow_and && FALSE !== strpos($value, ' ') )
			{
				$and[$key] = explode(' ', $value);
			}
			elseif ( CFT_core::$options->allow_or && FALSE !== strpos($value, ',') )
			{
				$value = self::array_to_sql(explode(',', $value));
				$case[] = $clause . "IN ($value)";
			}
			elseif ( FALSE !== strpos($value, '*') )
			{
				$value = str_replace('*', '%', $value);
				$case[] = $clause . "LIKE('$value')";
			}
			else
				$case[] = $clause . "= '$value'";
		}

		// CASE SQL
		$case = " AND CASE meta_key " . implode("\n", $case) . " END";

		// AND SQL
		foreach ( $and as $key => $clause )
		{
			$count = count($clause);

			$clause = self::array_to_sql($clause);

			$and_sql .= " AND {$wpdb->posts}.ID IN (
				SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = '$key'
				AND meta_value IN ($clause)
				GROUP BY post_id
				HAVING COUNT(*) >= $count
			)";
		}

		return $where . $case . $and_sql;
	}

	private static function array_to_sql($values)
	{
		foreach ( $values as &$val )
			$val = "'" . trim($val) . "'";

		return implode(',', $values);
	}

	static function posts_groupby($groupby)
	{
		global $wpdb;

		// Set having
		if ( CFT_core::$options->relevance )
			$having = ' HAVING COUNT(*) > 0';
		else
			$having = ' HAVING COUNT(*) = ' . count(self::$query_vars);

		$column = "{$wpdb->posts}.ID";

		// Add wp_posts.ID if it's not already added
		if ( FALSE === strpos($groupby, $column) )
		{
			if ( !empty($groupby) )
				$column .= ',';
			$groupby = $column . $groupby;
		}

		return $groupby . $having;
	}

	static function posts_orderby($orderby)
	{
		return "meta_rank DESC, " . $orderby;
	}

	// Sorts posts using penalties
	static function rank_by_order($posts)
	{
		self::remove_filters();

		if ( ! CFT_core::$options->rank_by_order )
			return $posts;

		self::set_penalties();
		
		foreach ( $posts as $post )
		{
			// Get relevant keys
			$values = get_post_custom($post->ID);

			// Substract penalties
			foreach ( self::$query_vars as $key => $value )
				if ( !@in_array($value, $values[$key]) )
					$post->meta_rank -= self::$penalties[$key] / (count(self::$query_vars) / 2);
		}

		usort($posts, array(__CLASS__, 'cmp_relevance'));

		return $posts;
	}

	static function cmp_relevance($postA, $postB)
	{
		$a = $postA->meta_rank;
		$b = $postB->meta_rank;

		if ($a == $b)
			return 0;

		return ($a > $b) ? -1 : 1;
	}

	// Penalties are based on the order of the query vars
	static function set_penalties()
	{
		foreach ( array_keys(self::$query_vars) as $key )
		{
			self::$penalties[$key] = strpos($_SERVER['QUERY_STRING'], $key.'=');
			self::$penalties[$key] = count(self::$penalties) - 1;
		}

		$values = array_values(self::$penalties);
		rsort($values);

		self::$penalties = array_combine(array_keys(self::$penalties), $values);
	}

	// Template tag
	static function meta_relevance($echo)
	{
		global $post;

		$relevance = round($post->meta_rank) . '%';

		if ( !$echo )
			return $relevance;

		echo $relevance;
	}
}

