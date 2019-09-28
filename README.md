# Jobbnorge Module for [Hogan](https://github.com/dekodeinteraktiv/hogan-core)

## Installation
Downloading this repository and place it in `wp-content/plugins`

## Available filters
- `dss/hogan/module/jobbnorge/items` : Set default number of items.
- `dss/hogan/module/jobbnorge/words` : Set default number of words per item.

## The module

<img src="assets/dss-hogan-jobbnorge.png">

## HTML code generated

```html
<ul class="list-items card-type-large">
	<li class="list-item">
		<div class="column">
			<p>Søknadsfrist: %1$s</p>
			<h3 class="entry-title"><a href="%2$s">%3$s</a></h3>
			<div class="entry-summary"><p>%4$s %5$s</p></div>
		</div>
	</li>
</ul>
```
1. Due date
2. Permalink to job add
3. Job title
4. Job decription
5. 'Read more' link
