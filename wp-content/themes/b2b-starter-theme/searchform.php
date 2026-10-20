<?php
/**
 * Styled search form.
 *
 * @package b2b-starter
 */

?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="screen-reader-text" for="site-search">Search for:</label>
	<input type="search" id="site-search" class="search-field" placeholder="Search products, guides, applications…" value="<?php echo esc_attr((string) get_search_query()); ?>" name="s">
	<button type="submit" class="search-submit">Search</button>
</form>

