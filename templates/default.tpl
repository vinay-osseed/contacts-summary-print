{if $contacts}
  <table>
    <tr>
    {foreach from=$contacts key=id item=contact}
      <td>
        {if $contact.contact_type == 'Organization'}
          <p>Organization Name: {if $contact.organization_name}{$contact.organization_name}{else}N/A{/if}</p>
        {elseif $contact.contact_type == 'Individual'}
          <p>Name: {if @$contact.prefix}{$contact.prefix} {/if}{if $contact.first_name}{$contact.first_name} {/if}{if $contact.last_name}{$contact.last_name}{/if}</p>
        {/if}
        <p>Address 1: {if $contact.street_address}{$contact.street_address}{else}N/A{/if}</p>
        <p>Address 2: {if $contact.supplemental_address_1}{$contact.supplemental_address_1}{else}N/A{/if}</p>
        <p>City: {if $contact.city}{$contact.city}{else}N/A{/if}</p>
        <p>State: {if $contact.state_province}{$contact.state_province}{else}N/A{/if}</p>
        <p>Zip Code: {if $contact.postal_code}{$contact.postal_code}{else}N/A{/if}</p>
        <p>Mobile Number: {if $contact.phone}{$contact.phone}{else}N/A{/if}</p>
      </td>
      {* split columns into 2 *}
      {if $id % 2 != 0}
        </tr><tr>
      {/if}
    {/foreach}
    </tr>
  </table>
{else}
  <p>No contacts found.</p>
{/if}
