const form = document.getElementById("quote-form");
const status = document.getElementById("quote-status");

function setStatus(text, kind) {
  if (!status) return;
  status.textContent = text;
  status.className = kind || "";
}

form?.addEventListener("submit", async (e) => {
  e.preventDefault();

  const button = form.querySelector("[type=submit]");
  const label = button?.textContent;

  form.querySelectorAll("[aria-invalid]").forEach((el) => el.removeAttribute("aria-invalid"));
  setStatus("");

  // An impatient double-click shouldn't file the same lead twice.
  if (button) {
    button.disabled = true;
    button.textContent = "Sending…";
  }

  const restore = () => {
    if (button) {
      button.disabled = false;
      button.textContent = label;
    }
  };

  try {
    const res = await fetch("lead.php", {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json" },
    });

    let data;
    try {
      data = await res.json();
    } catch {
      // Not JSON means PHP never ran — usually the page is being served by
      // something static. Say that plainly instead of showing a success
      // message for a lead that went nowhere.
      throw new Error("The form isn’t connected yet. Please call instead.");
    }

    if (res.status === 422 && data.errors) {
      const keys = Object.keys(data.errors);
      keys.forEach((key) => {
        form.querySelector(`[name="${key}"]`)?.setAttribute("aria-invalid", "true");
      });
      const firstField = form.querySelector(`[name="${keys[0]}"]`);
      firstField?.focus();
      setStatus(data.errors[keys[0]], "is-bad");
      restore();
      return;
    }

    if (!res.ok || !data.ok) {
      throw new Error(data.error || "Something broke on our end. Please call instead.");
    }

    // Only now is it honest to say we have it.
    form.hidden = true;
    setStatus("Got it. We’ll call you back.", "is-ok");
  } catch (err) {
    setStatus(err.message || "Couldn’t send that. Please call instead.", "is-bad");
    restore();
  }
});
