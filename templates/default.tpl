{if $contacts}
  <table>
    {foreach from=$contacts item=contact}
      <tr>
        <td>
          To<br>
          {if $contact.contact_type == 'Individual'}
            {if @$contact.prefix}{$contact.prefix} {/if}{if $contact.first_name}{$contact.first_name} {/if}{if $contact.last_name}{$contact.last_name}{/if}{if @$contact.prefix || $contact.first_name || $contact.last_name},{/if}<br>
          {/if}
          {if $contact.contact_type == 'Organization'}
            {if $contact.organization_name}{$contact.organization_name}{else}N/A{/if}{if $contact.organization_name},{/if}<br>
          {/if}
          {if $contact.job_title}{$contact.job_title},{/if}{if $contact.job_title}<br>{/if}
          {if $contact.contact_type == 'Individual'}
            {if $contact.current_employer}
              {$contact.current_employer},<br>
            {/if}
          {/if}
          {if $contact.supplemental_address_1}{$contact.supplemental_address_1},{/if}{if $contact.supplemental_address_1}<br>{/if}
          {if $contact.supplemental_address_2}{$contact.supplemental_address_2},{/if}{if $contact.supplemental_address_2}<br>{/if}
          {if $contact.city}{$contact.city}, {/if}{if $contact.state_province_name}{$contact.state_province_name}{/if}{if $contact.postal_code} - {$contact.postal_code}{/if}{if $contact.city || $contact.state_province_name || $contact.postal_code},{/if}<br>
          {if $contact.phone}
            {if @$contact.phone_type == 'Mobile'}
              Mobile: {$contact.phone}
            {elseif @$contact.phone_type == 'Phone'}
              Phone: {$contact.phone}
            {else}
              Phone: {$contact.phone}
            {/if}
          {/if}
        </td>
      </tr>
      <tr>
        <td class="row-gap"></td>
      </tr>
    {/foreach}
  </table>
{else}
  <p>No contacts found.</p>
{/if}
