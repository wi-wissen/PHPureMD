<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Die Tiere der Welt</title>
	<!-- https://picocss.com/docs/  -->
	<link rel="stylesheet" href="css/pico.css">
	<!-- https://fonts.bunny.net/ als europäische Alternative zu https://fonts.google.com/  -->
	<link href="https://fonts.bunny.net/css?family=bitter:400,400i,700" rel="stylesheet" />
	<!-- eigenes CSS  -->
	<style>
		:root {
			--font-family: Bitter, system-ui, -apple-system, "Segoe UI", "Roboto", "Ubuntu",
				"Cantarell", "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji",
				"Segoe UI Symbol", "Noto Color Emoji";
			--block-spacing-vertical:calc(var(--spacing) * 2) !important;
		}
		
		/* schöne Farben: https://tailwindcss.com/docs/customizing-colors#default-color-palette */
		[data-theme=light],
		:root:not([data-theme=dark]) {
		  --background-color: #fff;
		  --primary: #ea580c; /* orange-600 */
		  --primary-hover: #c2410c; /* orange-700 */
		  --primary-focus: #fdba74; /* orange-300 */
		}
		
		@media only screen and (prefers-color-scheme: dark) {
		  :root:not([data-theme]) {
			--background-color: #11191f;
			--primary: #fb923c; /* orange-400 */
			--primary-hover: #fdba74; /* orange-300 */
			--primary-focus: #c2410c; /* orange-700 */
		  }
		}
		
		html, body {
		  height: 100%;
		  --block-spacing-vertical:calc(var(--spacing) * 2);
		}

		body {
		  display: flex;
		  flex-direction: column;
		}
		
		header {
			color: var(--primary);
			font-weight: 700;
			font-size: 2rem;
		}
		
		main {
		  flex: 1; /* Füllt den verfügbaren Platz aus und mindestens die volle Seitenhöhe */
		}
		
		footer {
			color: var(--muted-color);
			font-size: 0.875rem;
		}
		
		.oembed {
			display: flex;
			flex-direction: column;
			column-gap: 0.5rem;
			margin: 0.5rem;
			border: solid #f5f5f5;
		}
		
		.oembed img {
			width: 100%;
		}
		
		.markdown p:has(img) {
		  text-align: center;
		}
		
		@media (min-width: 768px) {
			.oembed {
				flex-direction: row;
			}

			.oembed img {
				width: 8rem;
			}			
		}
			
		.text-center {
			text-align: center;
		}
		
		.object-cover {
			object-fit: cover;
		}
		
		.aspect-video {
			aspect-ratio: 16 / 9;
		}
		
		.aspect-square {
			aspect-ratio: 1 / 1;
		}
		
		.w-full {
			width: 100%;
		}
	</style>
    <!-- weitere Kopfinformationen -->
  </head>
  <body>
	<!-- Kommentare werden im Browser nicht angezeigt. -->
	<header class="text-center">
		<a href="/">Die Tiere der Welt</a>
	</header>
	<main class="container">
		<?php

		error_reporting(E_ERROR);

		function parseYaml($yamlText) {
			$result = [];

			$lines = explode("\n", $yamlText);

			foreach ($lines as $line) {
				$parts = explode(':', $line, 2);
				if (count($parts) === 2) {
					$key = trim($parts[0]);
					$value = trim($parts[1]);
					$result[$key] = $value;
				}
			}

			return $result;
		}

		function parseMarkdownFile($filePath) {
			$content = file_get_contents($filePath);

			$pattern = '/^---\r?\n(.*?)\r?\n---\r?\n(.*)/s';
			preg_match_all($pattern, $content, $matches);

			$frontmatter = parseYaml($matches[1][0]);
			$markdown = $matches[2][0] ?? $content;

			$entry = [
				"path" => pathinfo($filePath, PATHINFO_FILENAME),
				"title" => $frontmatter['title'] ?? pathinfo($filePath, PATHINFO_FILENAME),
				"img" => $frontmatter['img'] ?? "../img/404.webp",
				"date" => $frontmatter['date'] ?? "",
				"author" => $frontmatter['author'] ?? "",
				"text" => $markdown,
			];

			return $entry;
		}

		function parseMarkdownFiles($directory) {
			$files = scandir($directory);
			$result = [];

			foreach ($files as $file) {
				if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
					$filePath = "$directory/$file";
					$entry = parseMarkdownFile($filePath);
					$result[] = $entry;
				}
			}

			return $result;
		}
		
		function formatDateToBrowserLanguage($datetimeString) {
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$browserLanguage = getBrowserLanguage();
				setlocale(LC_TIME, $browserLanguage);
			} else {
				setlocale(LC_TIME, 'en_US'); // Standardwert, wenn die Browsersprache nicht ermittelt werden kann
			}
			
			$timestamp = strtotime($datetimeString);
			return strftime('%x %X', $timestamp);
		}

		function getBrowserLanguage() {
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				foreach ($languages as $language) {
					$language = explode(';', $language)[0];
					return trim($language);
				}
			}
			return 'de'; // Standardwert, wenn die Browsersprache nicht ermittelt werden kann
		}

		if (isset($_GET['article'])) {
			// show article
			$post = parseMarkdownFile('./blog/'.$_GET['article'].'.md');
			
		?>
		
		<!-- Hier wird ein Blogartikel angezeigt -->
		<img class="w-full object-cover aspect-video" src="/blog/<?php echo $post['img']?>">
		<?php if ($post['author'] && $post['date']): ?>
			<!-- Wenn Meta-Informationen vorhanden sind: -->
			<hgroup>
				<h2><?php echo $post['title']?></h2>
				<h3><?php echo $post['author']?> - <?php echo formatDateToBrowserLanguage($post['date'])?></h3>
			</hgroup>
		<?php else: ?>
			<!-- Wenn keine Meta-Informationen vorhanden sind: -->
			<h2><?php echo $post['title']?></h2>
		<?php endif; ?>
		<div class="markdown"><?php echo $post['text']?></div>
		
		<?php
		}
		else {
			// show index
			$posts = parseMarkdownFiles('./blog');
			
			usort($posts, function($a, $b) {
				return strcmp($b['date'], $a['date']);
			});
			
			foreach($posts as $post):

		?>
			<!-- Hier wird ein Element aus der Liste aller Blogartikel angezeigt -->
			<a class="oembed" href="<?php echo $post['path']?>">
				<img class="object-cover aspect-square" src="/blog/<?php echo $post['img']?>">
				<?php if ($post['author'] && $post['date']): ?>
					<!-- Wenn Meta-Informationen vorhanden sind: -->
					<hgroup>
						<h2><?php echo $post['title']?></h2>
						<h3><?php echo $post['author']?> - <?php echo formatDateToBrowserLanguage($post['date'])?></h3>
					</hgroup>
				<?php else: ?>
					<!-- Wenn keine Meta-Informationen vorhanden sind: -->
					<h2><?php echo $post['title']?></h2>
				<?php endif; ?>
			</a>
		
		<?php
			endforeach;
		}
		?>
	</main>
	<footer class="text-center">
		Ein Schulprojekt von mir.
	</footer>
	
	<script src="./js/marked.min.js"></script>
	<script>
		// Suche alle Elemente mit der Klasse `markdown` und gehe diese einzeln durch
		Array.from(document.getElementsByClassName("markdown")).forEach(article => {
			// ersetze relative Links
			let articleText = article.innerHTML.replace(/(!\[[^\]]*\])\(([^)]+)\)/g, (match, imageText, imageUrl) => {
				// Überprüfen, ob der Link relativ ist
				if (!imageUrl.startsWith('http')) {
					imageUrl = 'blog/' + imageUrl; // Füge das Präfix hinzu
				}
				return imageText + '(' + imageUrl + ')';
			});
			// Ersetzt den Inhalt des Elements durch den als HTML gerenderten Inhalt
			article.innerHTML = marked.parse(articleText);
		});
	</script>
  </body>
</html>