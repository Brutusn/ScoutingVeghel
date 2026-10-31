function reloadUserPage() {
  var form = document.forms.verhuring_toevoegen,
    tenant = form.elements.huurder,
    group = form.elements.groep;
  group.value = '';
  Array.from(group.options).some(function (option) {
    if (option.dataset.huurderId === tenant.value) {
      group.value = option.value;
      return true;
    }
    return false;
  });
  if (parent.frames.results)
    parent.frames.results.location.href = 'gebruikers.php?id=' + encodeURIComponent(tenant.value);
}
function selectOwnGroup() {
  var form = document.forms.verhuring_toevoegen,
    option = form.elements.groep.selectedOptions[0];
  if (option && option.dataset.huurderId) {
    form.elements.huurder.value = option.dataset.huurderId;
    reloadUserPage();
  }
}
var rentalForm = document.forms.verhuring_toevoegen;
if (rentalForm) {
  rentalForm.elements.huurder.addEventListener('change', reloadUserPage);
  rentalForm.elements.groep.addEventListener('change', selectOwnGroup);
}
