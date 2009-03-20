<?php

class CFT_query {
	private $matches;
	private $relevance;

	function __construct($matches, $relevance) {
		$this->matches = $matches;
		$this->relevance = $relevance;

		add_filter('posts_fields', array($this, 'posts_fields'));
		add_filter('posts_join', array($this, 'posts_join'));
		add_filter('posts_where', array($this, 'posts_where'));
		add_filter('posts_groupby', array($this, 'posts_groupby'));
		add_filter('posts_orderby', array($this, 'posts_orderby'));
	}

	function posts_fields($fields) {
		global $wpdb;

		return $fields . ", COUNT({$wpdb->posts}.ID) AS meta_rank";
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
		foreach ( $this->matches as $key => $value )
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
		if ( $this->relevance )
			$having = ' HAVING COUNT(post_id) > 0';
		else
			$having = ' HAVING COUNT(post_id) = ' . count($this->matches);

		$column = "{$wpdb->posts}.ID";

		// Add wp_posts.ID if it's not already added
		if ( FALSE === strpos($column, $groupby) ) {
			if ( !empty($groupby) )
				$column .=',';
			$groupby = $column . $groupby;
		}

		return $groupby . $having;
	}

	function posts_orderby($orderby) {
		return "meta_rank DESC, " . $orderby;
	}

	function the_relevance() {
		global $post;

		$relevance = round($post->meta_rank*100 / count($this->matches));

		echo $relevance . '%';
	}
}

