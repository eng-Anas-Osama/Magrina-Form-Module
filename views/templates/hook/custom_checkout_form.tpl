{block name="address_form"}
  <form method="POST" action="{$action}">

    <section class="form-fields">
      <div class="form-group row">
        <label class="col-md-3 form-control-label required" for="full_name">
          {l s='Full Name' d='Shop.Forms.Labels'}
        </label>
        <div class="col-md-6">
          <input class="form-control" name="full_name" type="text" value="{$full_name}" required>
        </div>
      </div>

      <div class="form-group row">
        <label class="col-md-3 form-control-label required" for="phone">
          {l s='Phone Number' d='Shop.Forms.Labels'}
        </label>
        <div class="col-md-6">
          <input class="form-control" name="phone" type="tel" value="{$phone}" required>
        </div>
      </div>

      <div class="form-group row">
        <label class="col-md-3 form-control-label required" for="government">
          {l s='Government' d='Shop.Forms.Labels'}
        </label>
        <div class="col-md-6">
          <select name="government" class="form-control form-control-select" required>
            <option value="">{l s='Select a government' d='Shop.Forms.Labels'}</option>
            {foreach $governments as $gov_id => $gov_name}
              <option value="{$gov_id}" {if $government == $gov_id}selected{/if}>{$gov_name}</option>
            {/foreach}
          </select>
        </div>
      </div>

      <div class="form-group row">
        <label class="col-md-3 form-control-label required" for="state">
          {l s='State' d='Shop.Forms.Labels'}
        </label>
        <div class="col-md-6">
          <select name="state" class="form-control form-control-select" required>
            <option value="">{l s='Select a state' d='Shop.Forms.Labels'}</option>
            {* States will be populated dynamically via JavaScript *}
          </select>
        </div>
      </div>

      <div class="form-group row">
        <label class="col-md-3 form-control-label required" for="address">
          {l s='Address' d='Shop.Forms.Labels'}
        </label>
        <div class="col-md-6">
          <textarea class="form-control" name="address" required>{$address}</textarea>
        </div>
      </div>

      <div class="form-group row">
        <label class="col-md-3 form-control-label" for="notes">
          {l s='Notes (Optional)' d='Shop.Forms.Labels'}
        </label>
        <div class="col-md-6">
          <textarea class="form-control" name="notes">{$notes}</textarea>
        </div>
      </div>

      <div class="form-group row">
        <div class="col-md-9 offset-md-3">
          <span class="custom-checkbox">
            <input name="create_account" type="checkbox" value="1">
            <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
            <label>{l s='Create an account' d='Shop.Theme.Checkout'}</label>
          </span>
        </div>
      </div>

      <div class="form-group row create-account-container" style="display: none;">
        <label class="col-md-3 form-control-label required" for="email">
          {l s='Email' d='Shop.Forms.Labels'}
        </label>
        <div class="col-md-6">
          <input class="form-control" name="email" type="email" value="{$email}">
        </div>
      </div>

    </section>

    <footer class="form-footer clearfix">
      <button class="btn btn-primary form-control-submit float-xs-right" type="submit">
        {l s='Save' d='Shop.Theme.Actions'}
      </button>
    </footer>

  </form>