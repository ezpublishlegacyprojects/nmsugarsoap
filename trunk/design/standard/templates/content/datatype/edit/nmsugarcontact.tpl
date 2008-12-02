{* DO NOT EDIT THIS FILE! Use an override template instead. *}

{default attribute_base=ContentObjectAttribute}

<select name="{$attribute_base}_contact_id_{$attribute.id}[]" multiple>
	{foreach $attribute.class_content.contacts_list as $contact}
	<option value="{$contact.id}" {if $attribute.content.id_array|contains($contact.id)}SELECTED{/if}>{$contact.first_name} {$contact.last_name} ({$contact.account_name})</option>
	{/foreach}
</select>

{/default}