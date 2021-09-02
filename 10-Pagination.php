<?php
/**
 * Pico Pagination Plugin
 *
 * @author Andrew Meyer
 * @link http://rewdy.com
 * @license http://opensource.org/licenses/MIT
 * @version 1.6
 */
class Pagination extends AbstractPicoPlugin {
	
	public $config = array();
	public $offset = 0;
	public $page_number = 0;
	public $total_pages = 1;
	public $paged_pages = array();

	public function __construct(Pico $pico)
	{
		parent::__construct($pico);

		$this->config = array(
			'limit' => 5,
			'next_text' => 'Next &gt;',
			'prev_text' => '&lt; Previous',
			'page_indicator' => 'page',
			'output_format'	=> 'links',
			'flip_links' => false,
			'filter_date' => true,
			'sub_page' => false,
		);
	}

	public function onConfigLoaded(&$settings)
	{
		// Pull config options for site config
		foreach ($this->config as $key=>$val) {
			if (isset($settings["pagination_" . $key])) {
				$this->config[$key] = $settings["pagination_" . $key];
			}	
		}
	}

	public function onPagesLoaded(&$pages, &$currentPage, &$previousPage, &$nextPage)
	{
		// Filter the pages returned based on the pagination options
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
		// sort $show_pages by $page['date']:
		$pdate = array_column($show_pages, 'date');
		array_multisort($pdate, SORT_DESC, $show_pages);
		// slice show_pages to the limit
		$show_pages = array_slice($show_pages, $this->offset, $this->config['limit']);
		// set filtered pages to paged_pages
		$this->paged_pages = $show_pages;
	}

	public function onPageRendering(&$twig, &$twigVariables, &$templateName)
	{
		// Set a bunch of view vars

		// send the paged pages in separate var
		if ($this->paged_pages)
			$twigVariables['paged_pages'] = $this->paged_pages;

		// set var for page_number
		if ($this->page_number)
			$twigVariables['page_number'] = $this->page_number;

		// set var for total pages
		if ($this->total_pages)
			$twigVariables['total_pages'] = $this->total_pages;

		// set var for page_indicator
		$twigVariables['page_indicator'] = $this->config['page_indicator'];
		
		// build pagination links
		// set next and back link vars to empty. links will be added below if they are available.
		$twigVariables['next_page_link'] = $twigVariables['prev_page_link'] = '';
		$twigVariables['next_page_url'] = $twigVariables['prev_page_url'] = '';

		// Array of markup that will be joined to build the pagination links
		$pagination_parts = array();
		// If we have a previous link
		if ($this->page_number > 1) {
			$prev_path = $twigVariables["prev_page_url"] = $this->getBaseUrl() . $this->config['page_indicator'] . '/' . ($this->page_number - 1);
			$pagination_parts['prev_link'] = $twigVariables['prev_page_link'] = '<a href="' . $prev_path . '" id="prev_page_link">' . $this->config['prev_text'] . '</a>';
		}
		// If we have a next link
		if ($this->page_number < $this->total_pages) {
			$next_path = $twigVariables["next_page_url"] = $this->getBaseUrl() . $this->config['page_indicator'] . '/' . ($this->page_number + 1);
			$pagination_parts['next_link'] = $twigVariables['next_page_link'] = '<a href="' . $next_path . '" id="next_page_link">' . $this->config['next_text'] . '</a>';
		}

		// Reverse order if flip_links is on
		if ($this->config['flip_links']) {
			$pagination_parts = array_reverse($pagination_parts);
		}

		// create pagination links output
		if ($this->config['output_format'] == "list") {
			$twigVariables['pagination_links'] = '<ul id="pagination"><li>' . implode('</li><li>', array_values($pagination_parts)) . '</li></ul>';
		} else {
			$twigVariables['pagination_links'] = implode(' ', array_values($pagination_parts));
		}

		// set page of page var
		$twigVariables['page_of_page'] = "Page " . $this->page_number . " of " . $this->total_pages . ".";
	}

	public function onRequestUrl(&$url)
	{
		// checks for page # in URL
		$pattern = '/' . $this->config['page_indicator'] . '\/[0-9]*$/';
		if (preg_match($pattern, $url)) {
			// Override 404 header
			header($_SERVER['SERVER_PROTOCOL'].' 200 OK');

			$page_numbers = explode('/', $url);
			$page_number = $page_numbers[count($page_numbers)-1];
			$this->page_number = $page_number;
			if ($this->config['sub_page']) {
				$url = $this->config['page_indicator'];
			} else {
				$url = preg_replace($pattern, '', $url);
			}
		} else {
			$this->page_number = 1;
		}
	}
}
