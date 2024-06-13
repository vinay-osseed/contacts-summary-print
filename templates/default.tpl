{if $contacts}
  <table>
    <tr>
    {foreach from=$contacts key=id item=contact}
      <td>
        {if $contact.contact_type == 'Organization'}
          <p style="margin: 5px;">Organization Name: {if $contact.organization_name}{$contact.organization_name}{else}N/A{/if}</p>
        {elseif $contact.contact_type == 'Individual'}
          <p style="margin: 5px;">Name: {if @$contact.prefix}{$contact.prefix} {/if}{if $contact.first_name}{$contact.first_name} {/if}{if $contact.last_name}{$contact.last_name}{/if}</p>
        {/if}
        <p style="margin: 5px;">Address 1: {if $contact.street_address}{$contact.street_address}{else}N/A{/if}</p>
        <p style="margin: 5px;">Address 2: {if $contact.supplemental_address_1}{$contact.supplemental_address_1}{else}N/A{/if}</p>
        <p style="margin: 5px;">City: {if $contact.city}{$contact.city}{else}N/A{/if}</p>
        <p style="margin: 5px;">State: {if $contact.state_province}{$contact.state_province}{else}N/A{/if}</p>
        <p style="margin: 5px;">Zip Code: {if $contact.postal_code}{$contact.postal_code}{else}N/A{/if}</p>
        <p style="margin: 5px;">Mobile Number: {if $contact.phone}{$contact.phone}{else}N/A{/if}</p>
      </td>
      {* split columns into 2 *}
      {if $id % 2 != 0}
        {* add extra 1 empty row *}
        </tr><tr><td></td></tr><tr>
      {/if}
    {/foreach}
    </tr>
  </table>
{else}
  <p>No contacts found.</p>
{/if}
