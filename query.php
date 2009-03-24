<?php

class CFT_query {
	private $query_vars;
	private $options;
	private $filters;
	private $penalties;

	function __construct($query_vars, $options) {
		$this->query_vars = $query_vars;
		$this->options = $options;

		// Set filters		
		$this->filters = array(
			'posts_fields' => array($this, 'posts_fields'),
			'posts_join' => array($this, 'posts_join'),
			'posts_where' => array($this, 'posts_where'),
			'posts_groupby' => array($this, 'posts_groupby'),
			'posts_orderby' => array($this, 'posts_orderby'),
			'the_posts' => array($this, 'rank_by_order'),
		);

		// And add them
		add_action('pre_get_posts', array($this, 'add_filters'));
	}

	// Adds filters only to main query
	function add_filters($obj) {
		if ( $GLOBALS['wp_query'] != $obj )
			return;
			
		foreach ( $this->filters as $name => $callback )
			add_filter($name, $callback);
	}

	// Remove all filters, including this one
	function remove_filters() {
		foreach ( $this->filters as $name => $callback )
			remove_filter($name, $callback);
	}

	function posts_fields($fields) {
		global $wpdb;

		$nr = count($this->query_vars);

		return $fields . ", COUNT({$wpdb->posts}.ID) * 100 / {$nr} AS meta_rank";
	}

	function posts_join($join) {
		global $wpdb;

		return $join . " JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id)";
	}

	function posts_where($where) {
		global $wpdb;

		if ( is_singular() ) {
			CFT_core::make_canonical();

			// Get posts instead of front page
			$where = " AND {$wpdb->posts}.post_type = 'post' AND {$wpdb->posts}.post_status = 'publish'";
		}

		// Build CASE clauses
		foreach ( $this->query_vars as $key => $value )
			if ( empty($value) )
				$clauses[] = $wpdb->prepare("WHEN '%s' THEN meta_value IS NOT NULL", $key);
			elseif ( FALSE === strpos($value, '*') )
				$clauses[] = $wpdb->prepare("WHEN '%s' THEN meta_value = '%s'", $key, $value);
			else {
				$value = str_replace('*', '%', $value);
				$clauses[] = $wpdb->prepare("WHEN '%s' THEN meta_value LIKE('%s')", $key, $value);
			}
		$clauses = implode("\n", $clauses);

		return $where . " AND CASE meta_key {$clauses} END";
	}

	function posts_groupby($groupby) {
		global $wpdb;

		// Set having
		if ( $this->options['relevance'] )
			$having = ' HAVING COUNT(post_id) > 0';
		else
			$having = ' HAVING COUNT(post_id) = ' . count($this->query_vars);

		$column = "{$wpdb->posts}.ID";

		// Add wp_posts.ID if it's not already added
		if ( FALSE === strpos($groupby, $column) ) {
			if ( !empty($groupby) )
				$column .=',';
			$groupby = $column . $groupby;
		}

		return $groupby . $having;
	}

	function posts_orderby($orderby) {
		return "meta_rank DESC, " . $orderby;
	}

	function rank_by_order($posts) {
		$this->remove_filters();

		if ( ! $this->options['rank_by_order'] )
			return $posts;

		$this->set_penalties();
		
		foreach ( $posts as $post ) {
			// Get relevant keys
			$values = get_post_custom($post->ID);

			// Substract penalties
			foreach ($this->query_vars as $key => $value)
				if ( !@in_array($value, $values[$key]) )
					$post->meta_rank -= $this->penalties[$key] / (count($this->query_vars) / 2);
		}

		usort($posts, array($this, 'cmp_relevance'));

		return $posts;
	}

	function cmp_relevance($postA, $postB) {
		$a = $postA->meta_rank;
		$b = $postB->meta_rank;

		if ($a == $b)
			return 0;

		return ($a > $b) ? -1 : 1;
	}

	// Penalties are based on the order of the query vars
	function set_penalties() {
		foreach ( array_keys($this->query_vars) as $key ) {
			$this->penalties[$key] = strpos($_SERVER['QUERY_STRING'], $key.'=');
			$this->penalties[$key] = count($this->penalties) - 1;
		}

		$values = array_values($this->penalties);
		rsort($values);

		$this->penalties = array_combine(array_keys($this->penalties), $values);
	}

	function the_relevance() {
		global $post;

		echo round($post->meta_rank) . '%';
	}
}

