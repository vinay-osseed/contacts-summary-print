{literal}
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <title>Print Contacts Summary</title>
  </head>

  <body>
  {/literal}
  {$templateContent}
  <h3>{include file="CRM/Contact/Form/Task.tpl"}</h3>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
  {literal}
  </body>

  </html>
{/literal}