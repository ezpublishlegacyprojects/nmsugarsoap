{foreach $attribute.content.data as $contact}

<div class="sugar-contact">
	<strong>{$contact.first_name} {$contact.last_name}</strong> ({$contact.account_name})<br />
	<table>
		{if ne($contact.phone_work, '')}
		<tr>
			<td>
				<img src={"images/sugaricons/house.png"|ezdesign} alt="Kontortlf" />
			</td>
			<td>
				{$contact.phone_work}
			</td>
		</tr>
		{/if}
		{if ne($contact.phone_mobile, '')}
		<tr>
			<td>
				<img src={"images/sugaricons/phone.png"|ezdesign} alt="Mobil" />
			</td>
			<td>
				{$contact.phone_mobile}
			</td>
		</tr>
		{/if}
		{if ne($contact.email1, '')}
		<tr>
			<td>
				<img src={"images/sugaricons/email.png"|ezdesign} alt="E-post" />
			</td>
			<td>
				<a href="mailto:{$contact.email1}">{$contact.email1}</a>
			</td>
		</tr>
		{/if}
	</table>
</div>
{delimiter}<br />{/delimiter}
{/foreach}