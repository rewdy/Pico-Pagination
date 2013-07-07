<?php
/**
 * Tags for Pico plugin
 *
 * @author Andrew Meyer
 * @link http://rewdy.com
 * @license http://opensource.org/licenses/MIT
 */
class Pagination {
	
	public $config = array();
	public $offset = 0;
	public $page_number = 0;
	public $total_pages = 1;
	public $paged_pages = array();

	public function __construct() {
		$this->config = array(
			'limit' => 5,
			'next_text' => 'Next >',
			'prev_text' => '< Previous',
			'page_indicator' => 'page',
			'output_format'	=> 'links', // can be links or list
			'filter_date' => true
		);
	}

	public function config_loaded(&$settings) {
		/* Pull config options for site config */
		if (isset($settings['pagination_limit']))
			$this->config['limit'] = $settings['pagination_limit'];
		if (isset($settings['pagination_next_text']))
			$this->config['next_text'] = $settings['pagination_next_text'];
		if (isset($settings['pagination_prev_text']))
			$this->config['prev_text'] = $settings['pagination_prev_text'];
		if (isset($settings['pagination_page_indicator']))
			$this->config['page_indicator'] = $settings['pagination_page_indicator'];
		if (isset($settings['pagination_output_format']))
			$this->config['output_format'] = $settings['pagination_output_format'];
		if (isset($settings['pagination_filter_date']))
			$this->config['filter_date'] = $settings['pagination_filter_date'];
	}

	public function get_pages(&$pages, &$current_page, &$prev_page, &$next_page)
	{
		/* Filter the pages returned based on the pagination options */
		$this->offset = ($this->page_number-1) * $this->config['limit'];
		// if filter_date is true, it filters so only dated items are returned.
		if ($this->config['filter_date']) {
			$show_pages = array();
			foreach($pages as $key=>$page) {
				if ($page['date']) {
					$show_pages[$key] = $page;
				}
			}
		} else {
			$show_pages = $pages;
		}
		// get total pages before show_pages is sliced
		$this->total_pages = ceil(count($show_pages) / $this->config['limit']);
		// slice show_pages to the limit
		$show_pages = array_slice($show_pages, $this->offset, $this->config['limit']);
		// set pages to show_pages
		$this->paged_pages = $show_pages;
		//$pages = $show_pages;
	}

	/* Set a view var to indicate a tag has been requested */
	public function before_render(&$twig_vars, &$twig) {
		global $config;

		// send the paged pages in separate var
		if ($this->paged_pages)
			$twig_vars['paged_pages'] = $this->paged_pages;

		// set var for page_number
		if ($this->page_number)
			$twig_vars['page_number'] = $this->page_number;

		// set var for total pages
		if ($this->total_pages)
			$twig_vars['total_pages'] = $this->total_pages;

		// build pagination links
		// set next and back link vars to empty. links will be added below if they are available.
		$twig_vars['next_page'] = $twig_vars['prev_page'] = '';
		$pagination_parts = array();
		if ($this->page_number > 1) {
			$prev_path = $config['base_url'] . '/page/' . ($this->page_number - 1);
			$pagination_parts['prev_link'] = $twig_vars['prev_page'] = '<a href="' . $prev_path . '" id="prev_page_link">' . $this->config['prev_text'] . '</a>';
		}
		if ($this->page_number < $this->total_pages) {
			$next_path = $config['base_url'] . '/page/' . ($this->page_number + 1);
			$pagination_parts['next_link'] = $twig_vars['next_page'] = '<a href="' . $next_path . '" id="next_page_link">' . $this->config['next_text'] . '</a>';
		}

		// create pagination links output
		if ($this->config['output_format'] == "list") {
			$twig_vars['pagination_links'] = '<ul id="pagination"><li>' . implode('</li><li>', array_values($pagination_parts)) . '</li></ul>';
		} else {
			$twig_vars['pagination_links'] = implode(' ', array_values($pagination_parts));
		}

		// set page of page var
		$twig_vars['page_of_page'] = "Page " . $this->page_number . " of " . $this->total_pages . ".";
	}

	/* When tagged/string is called as a URI, reroute back to index and save the slug */
	public function request_url(&$url) {
		$pattern = '/' . $this->config['page_indicator'] . '\//';
		if (preg_match($pattern, $url)) {
			$page_numbers = explode('/', $url);
			$page_number = $page_numbers[count($page_numbers)-1];
			$this->page_number = $page_number;
			$url = '';
		} else {
			$this->page_number = 1;
		}
	}
}

?>