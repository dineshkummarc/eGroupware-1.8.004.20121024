<?xml version="1.0" encoding="{encoding}"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
	<channel rdf:about="{link}">
		<title><![CDATA[{title}]]></title>
		<link>{link}</link>
		<description><![CDATA[{description}]]></description>

		<image rdf:resource="{img_url}" />

		<items>
			<rdf:Seq>
				<!-- BEGIN seq -->
					<rdf:li rdf:resource="{item_link}"/>
				<!-- END seq -->
			</rdf:Seq>
		</items>
	</channel>

	<image rdf:resource="{img_url}" />
		<title><![CDATA[{img_title}]]></title>
		<link>{img_link}</link>
		<url>{img_url}</url>
	</image>
<!-- BEGIN item -->
	<item>
		<title><![CDATA[{subject}]]></title>
		<link>{item_link}</link>
		<description><![CDATA[{teaser}]]></description>
	</item>
<!-- END item -->
</rdf:RDF>
