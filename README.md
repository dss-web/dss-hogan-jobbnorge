# Jobbnorge Module for [Hogan](https://github.com/dekodeinteraktiv/hogan-core)

List job postings from [Jobbnorge](https://www.jobbnorge.no/search/en)

## The module

<img src="assets/dss-hogan-jobbnorge.png">

## Installation
Downloading this repository and place it in `wp-content/plugins`

## Available filters
- `dss/hogan/module/jobbnorge/items` : Set default number of items.
- `dss/hogan/module/jobbnorge/words` : Set default number of words per item.
- `dss/hogan/module/jobbnorge/html/wrapper/start`: The HTML start wrapper, the default is:
	```php
	<ul class="list-items card-type-large">
	```
- `dss/hogan/module/jobbnorge/html/wrapper/end`: The HTML end wrapper, the default is:
	```php
	</ul>
	```
- `dss/hogan/module/jobbnorge/html/item` The HTML for each item, the default is:
	```html
	<ul class="list-items card-type-large">
		<li class="list-item">
			<div class="column">
				<p>%1$s %2$s</p>
				<h3 class="entry-title"><a href="%3$s">%4$s</a></h3>
				<div class="entry-summary"><p>%5$s %6$s</p></div>
			</div>
		</li>
	</ul>
	```
	- `%1$s`: Due date prefix
	- `%2$s`: Due date
	- `%3$s`: Permalink to job add
	- `%4$s`: Job title
	- `%5$s`: Job decription
	- `%6$s`: 'Read more' link and text, ` ... <a href="[Permalink to job add]">Read more</a>`


## Copyright and License

Jobbnorge Module for Hogan is copyright 2019 Per Soderlind

Jobbnorge Module for Hogan is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

Jobbnorge Module for Hogan is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU Lesser General Public License along with the Extension. If not, see http://www.gnu.org/licenses/.



