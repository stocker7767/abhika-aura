(function () {
  "use strict";
  document.querySelectorAll(".aa-enquiry-form").forEach(function (form) {
    const submit = form.querySelector('button[type="submit"]');
    const phone = form.querySelector('input[name="aa_phone"]');
    function resetButton() {
      if (submit) {
        submit.disabled = false;
        submit.textContent = "Send enquiry ↗";
      }
    }
    function validatePhone() {
      if (!phone) return;
      const value = phone.value.trim();
      const digits = value.replace(/\D/g, "");
      const valid =
        /^\+?[0-9().\s-]{6,40}$/.test(value) &&
        digits.length >= 6 &&
        digits.length <= 15;
      phone.setCustomValidity(
        value && !valid
          ? "Enter a telephone number containing 6 to 15 digits."
          : "",
      );
    }
    if (phone) phone.addEventListener("input", validatePhone);
    window.addEventListener("pageshow", resetButton);
    form.addEventListener("submit", function (event) {
      validatePhone();
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
      }
      if (submit) {
        submit.disabled = true;
        submit.textContent = "Sending…";
      }
    });
    const errors = form.querySelector(".aa-enquiry-errors");
    if (errors) errors.focus();
  });
})();
