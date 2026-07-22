"use strict";

const FormValidator = {
  rules: {
    required: (val) => val.trim().length > 0,
    email: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
    phone: (val) => /^[+]?[\d\s\-()]{7,15}$/.test(val),
    minLength: (val, n) => val.trim().length >= n,
    maxLength: (val, n) => val.trim().length <= n,
    password: (val) => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(val),
    match: (val, selector) => {
      const other = document.querySelector(selector);
      return other ? val === other.value : false;
    },
  },

  messages: {
    required: "This field is required.",
    email: "Please enter a valid email address.",
    phone: "Please enter a valid phone number.",
    minLength: (n) => `Minimum ${n} characters required.`,
    maxLength: (n) => `Maximum ${n} characters allowed.`,
    password:
      "Password must be 8+ characters with uppercase, lowercase, and a number.",
    match: "Passwords do not match.",
  },

  validate(field) {
    const rules = field.dataset.validate?.split(" ") || [];
    let isValid = true;
    let message = "";

    for (const rule of rules) {
      const [name, param] = rule.split(":");
      const fn = this.rules[name];
      if (!fn) continue;

      const passed = fn(field.value, param);
      if (!passed) {
        isValid = false;
        const msg = this.messages[name];
        message = typeof msg === "function" ? msg(param) : msg;
        break;
      }
    }

    this.setFieldState(field, isValid, message);
    return isValid;
  },

  setFieldState(field, isValid, message = "") {
    const group = field.closest(".form-group");
    if (!group) return;

    const errorEl = group.querySelector(".form-error");
    field.classList.toggle("error", !isValid);
    field.classList.toggle("success", isValid && field.value.trim().length > 0);

    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.toggle("show", !isValid);
    }
  },

  bindForm(formEl) {
    if (!formEl) return;
    const fields = formEl.querySelectorAll("[data-validate]");

    fields.forEach((field) => {
      field.addEventListener("blur", () => this.validate(field));
      field.addEventListener("input", () => {
        if (field.classList.contains("error")) this.validate(field);
      });
    });

    formEl.addEventListener("submit", (e) => {
      let allValid = true;

      fields.forEach((field) => {
        if (!this.validate(field)) allValid = false;
      });

      if (!allValid) {
        e.preventDefault();
        const firstError = formEl.querySelector(".form-control.error");
        firstError?.focus();
        showToast("Please fix the errors before submitting.", "error");
      } else {
        // Call handler if provided, otherwise allow default submission
        const handler = formEl.dataset.onValid;
        if (handler && window[handler]) {
          e.preventDefault();
          window[handler](formEl);
        }
        // If no handler, form submits naturally
      }
    });
  },
};

function initPasswordToggles() {
  document
    .querySelectorAll(".input-action[data-toggle-password]")
    .forEach((btn) => {
      btn.addEventListener("click", () => {
        const target = document.querySelector(btn.dataset.togglePassword);
        if (!target) return;
        const isHidden = target.type === "password";
        target.type = isHidden ? "text" : "password";
        btn.textContent = isHidden ? "🙈" : "👁️";
      });
    });
}

function initRoleSelector() {
  const cards = document.querySelectorAll(".role-card");
  const continueBtn = document.querySelector("#role-continue-btn");
  let selectedRole = null;

  if (!cards.length) return;

  cards.forEach((card) => {
    card.addEventListener("click", () => {
      cards.forEach((c) => c.classList.remove("selected"));
      card.classList.add("selected");
      selectedRole = card.dataset.role;

      if (continueBtn) {
        continueBtn.removeAttribute("disabled");
        continueBtn.classList.remove("btn-ghost");
        continueBtn.classList.add("btn-primary");
      }
    });
  });

  continueBtn?.addEventListener("click", () => {
    if (!selectedRole) {
      showToast("Please select your role to continue.", "warning");
      return;
    }
    const destinations = {
      donor: "register-donor.html",
      requester: "register-requester.html",
    };
    window.location.href = destinations[selectedRole] || "#";
  });
}

const MultiStepForm = {
  current: 0,
  steps: [],

  init(formEl) {
    if (!formEl) return;
    this.form = formEl;
    this.steps = Array.from(formEl.querySelectorAll(".form-step"));
    this.progressSteps = Array.from(formEl.querySelectorAll(".progress-step"));
    this.nextBtns = Array.from(formEl.querySelectorAll(".step-next"));
    this.prevBtns = Array.from(formEl.querySelectorAll(".step-prev"));

    if (!this.steps.length) return;

    this.showStep(0);

    this.nextBtns.forEach((btn) => {
      btn.addEventListener("click", () => this.next());
    });

    this.prevBtns.forEach((btn) => {
      btn.addEventListener("click", () => this.prev());
    });
  },

  showStep(index) {
    this.steps.forEach((step, i) => {
      step.classList.toggle("hidden", i !== index);
    });

    this.progressSteps.forEach((step, i) => {
      step.classList.remove("active", "completed");
      if (i < index) step.classList.add("completed");
      if (i === index) step.classList.add("active");
    });

    this.current = index;
    window.scrollTo({ top: 0, behavior: "smooth" });
  },

  next() {
    const currentStep = this.steps[this.current];
    const fields = currentStep.querySelectorAll("[data-validate]");
    let valid = true;

    fields.forEach((field) => {
      if (!FormValidator.validate(field)) valid = false;
    });

    if (!valid) {
      showToast("Please fill all required fields correctly.", "error");
      return;
    }

    if (this.current < this.steps.length - 1) {
      this.showStep(this.current + 1);
    }
  },

  prev() {
    if (this.current > 0) this.showStep(this.current - 1);
  },
};

function initScrollAnimations() {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.animationPlayState = "running";
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1, rootMargin: "0px 0px -50px 0px" },
  );

  document
    .querySelectorAll(
      ".animate-fade-in-up, .animate-fade-in, .animate-scale-in",
    )
    .forEach((el) => {
      el.style.animationPlayState = "paused";
      observer.observe(el);
    });
}

function animateCounter(el, target, duration = 1800) {
  let start = null;
  const startVal = 0;

  function step(timestamp) {
    if (!start) start = timestamp;
    const progress = Math.min((timestamp - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    const current = Math.floor(startVal + (target - startVal) * ease);
    el.textContent = current.toLocaleString();
    if (progress < 1) requestAnimationFrame(step);
  }

  requestAnimationFrame(step);
}

function initCounters() {
  const counterEls = document.querySelectorAll("[data-counter]");
  if (!counterEls.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const target = parseInt(entry.target.dataset.counter, 10);
          animateCounter(entry.target, target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 },
  );

  counterEls.forEach((el) => observer.observe(el));
}

function initBloodTypeSelector() {
  document
    .querySelectorAll('.blood-type-option input[type="radio"]')
    .forEach((input) => {
      input.addEventListener("change", () => {
        const selected = input.value;
        const display = document.querySelector("#selected-blood-type-display");
        if (display) display.textContent = selected;
      });
    });
}

function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener("click", (e) => {
      const target = document.querySelector(link.getAttribute("href"));
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });
}

function handleLoginSubmit(formEl) {
  const btn = formEl.querySelector('[type="submit"]');
  btn.innerHTML = '<span class="spinner"></span> Signing in…';
  btn.disabled = true;

  // Submit the form to the backend
  formEl.submit();
}

function handleRegisterSubmit(formEl) {
  const btn = formEl.querySelector('[type="submit"]');
  btn.innerHTML = '<span class="spinner"></span> Registering…';
  btn.disabled = true;

  // Submit the form to the backend
  formEl.submit();
}

function handleContactSubmit(formEl) {
  const btn = formEl.querySelector('[type="submit"]');
  if (btn) {
    btn.innerHTML = '<span class="spinner"></span> Sending…';
    btn.disabled = true;
  }
  formEl.submit();
}

function handleBloodRequestSubmit(formEl) {
  const btn = formEl.querySelector('[type="submit"]');
  if (btn) {
    btn.innerHTML = '<span class="spinner"></span> Submitting…';
    btn.disabled = true;
  }
  formEl.submit();
}

function initTabs() {
  document.querySelectorAll(".tab-group").forEach((group) => {
    const tabs = group.querySelectorAll(".tab-btn");
    const panels = group.querySelectorAll(".tab-panel");

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        const target = tab.dataset.tab;

        tabs.forEach((t) => t.classList.remove("active"));
        panels.forEach((p) => p.classList.add("hidden"));

        tab.classList.add("active");
        const panel = group.querySelector(`.tab-panel[data-tab="${target}"]`);
        panel?.classList.remove("hidden");
      });
    });

    tabs[0]?.click();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  ThemeManager.init();
  Navbar.init();
  Sidebar.init();

  initPasswordToggles();
  initRoleSelector();
  initScrollAnimations();
  initCounters();
  initBloodTypeSelector();
  initSmoothScroll();
  initTabs();

  FormValidator.bindForm(document.querySelector("#login-form"));
  FormValidator.bindForm(document.querySelector("#register-donor-form"));
  FormValidator.bindForm(document.querySelector("#register-requester-form"));
  FormValidator.bindForm(document.querySelector("#contact-form"));
  FormValidator.bindForm(document.querySelector("#blood-request-form"));

  MultiStepForm.init(document.querySelector("#register-donor-form"));
  MultiStepForm.init(document.querySelector("#register-requester-form"));
});

window.handleLoginSubmit = handleLoginSubmit;
window.handleRegisterSubmit = handleRegisterSubmit;
window.handleContactSubmit = handleContactSubmit;
window.handleBloodRequestSubmit = handleBloodRequestSubmit;
window.showToast = showToast;
window.ThemeManager = ThemeManager;
