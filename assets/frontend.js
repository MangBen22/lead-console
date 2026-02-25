(() => {
  const root = document.querySelector(".lc-fe");
  if (!root) return;

  root.querySelectorAll(".lc-toggle-password").forEach((btn) => {
    btn.addEventListener("click", () => {
      const row = btn.closest(".lc-password-row");
      const input = row?.querySelector(".lc-password-input");
      if (!input) return;
      const showing = input.type === "text";
      input.type = showing ? "password" : "text";
      btn.textContent = showing ? "Show" : "Hide";
      btn.setAttribute("aria-label", showing ? "Show password" : "Hide password");
    });
  });

  const tabButtons = root.querySelectorAll(".lc-tab-btn");
  const tabPanels = root.querySelectorAll(".lc-tab-panel");
  const activateTab = (tab) => {
    tabButtons.forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.tab === tab);
    });
    tabPanels.forEach((panel) => {
      panel.classList.toggle("is-active", panel.dataset.tab === tab);
    });
  };
  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const tab = btn.dataset.tab;
      if (!tab) return;
      activateTab(tab);
    });
  });

  const authToggles = root.querySelectorAll(".lc-auth-toggle");
  const authPanels = root.querySelectorAll(".lc-auth-panel");
  authToggles.forEach((btn) => {
    btn.addEventListener("click", () => {
      const target = btn.dataset.target || "";
      authPanels.forEach((panel) => {
        panel.hidden = panel.dataset.authPanel !== target;
      });
    });
  });

  root.querySelectorAll(".lc-open-run-advanced").forEach((btn) => {
    btn.addEventListener("click", () => {
      const form = btn.closest("form");
      const panel = form?.querySelector(".lc-run-advanced");
      if (!panel) return;
      const open = panel.hidden;
      panel.hidden = !open;
      btn.textContent = open ? "Hide Advanced Options" : "Advanced Options";
    });
  });

  const modal = root.querySelector(".lc-tutorial");
  if (!modal) return;

  const openBtn = root.querySelector(".lc-open-tutorial");
  const closeBtn = modal.querySelector(".lc-tutorial-close");
  const panel = modal.querySelector(".lc-tutorial-panel");
  const head = modal.querySelector(".lc-tutorial-head");
  const nextBtn = modal.querySelector(".lc-next-step");
  const prevBtn = modal.querySelector(".lc-prev-step");
  const copy = modal.querySelector(".lc-step-copy");
  const check = modal.querySelector(".lc-step-check");
  const progress = modal.querySelector(".lc-tutorial-progress");

  const userId = root.dataset.userId || "guest";
  const storageKey = `lcTutorialComplete_${userId}`;

  const steps = [
    {
      selector: null,
      text: "Welcome to 5N2 Digital Lead Console. You can manage leads, run discovery jobs, monitor outputs, and operate within compliance guardrails.",
    },
    {
      selector: "#lc-section-leads",
      text: "Leads section: import CSV, TSV, TXT, JSON, or XLSX files. The system maps fields automatically and reports skipped rows if data is incomplete.",
    },
    {
      selector: "#lc-section-runs",
      text: "Runs section: queue a discovery run by entering search query and city, then set max places. Example: Query 'Plumber', City 'Dallas', Max 25.",
    },
    {
      selector: "#lc-section-compliance",
      text: "Compliance section: use only lawful/public sources and approved APIs. Avoid prohibited scraping or unauthorized automation.",
    },
  ];

  let index = 0;
  let completion = new Array(steps.length).fill(false);

  const renderStep = () => {
    root.querySelectorAll(".lc-step-focus").forEach((el) => el.classList.remove("lc-step-focus"));
    const step = steps[index];
    copy.textContent = step.text;
    check.checked = completion[index];
    progress.textContent = `Step ${index + 1} of ${steps.length}`;
    prevBtn.disabled = index === 0;
    nextBtn.textContent = index === steps.length - 1 ? "Finish" : "Next";
    nextBtn.disabled = !check.checked;

    if (step.selector) {
      const target = root.querySelector(step.selector);
      if (target) {
        const panel = target.closest(".lc-tab-panel");
        const tab = panel?.dataset?.tab;
        if (tab) activateTab(tab);
        target.classList.add("lc-step-focus");
        target.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  };

  const open = () => {
    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
    renderStep();
  };

  const close = () => {
    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");
  };

  check.addEventListener("change", () => {
    completion[index] = check.checked;
    nextBtn.disabled = !check.checked;
  });

  nextBtn.addEventListener("click", () => {
    if (!completion[index]) return;
    if (index === steps.length - 1) {
      localStorage.setItem(storageKey, "1");
      close();
      return;
    }
    index += 1;
    renderStep();
  });

  prevBtn.addEventListener("click", () => {
    if (index === 0) return;
    index -= 1;
    renderStep();
  });

  closeBtn.addEventListener("click", close);
  openBtn?.addEventListener("click", open);

  window.addEventListener("keydown", (event) => {
    if (modal.hidden) return;
    if (event.key === "ArrowRight" && !nextBtn.disabled) nextBtn.click();
    if (event.key === "ArrowLeft" && !prevBtn.disabled) prevBtn.click();
  });

  let dragOn = false;
  let dragOffsetX = 0;
  let dragOffsetY = 0;
  head?.addEventListener("mousedown", (event) => {
    dragOn = true;
    const rect = panel.getBoundingClientRect();
    dragOffsetX = event.clientX - rect.left;
    dragOffsetY = event.clientY - rect.top;
    panel.style.position = "fixed";
    panel.style.margin = "0";
  });
  window.addEventListener("mousemove", (event) => {
    if (!dragOn) return;
    panel.style.left = `${Math.max(8, event.clientX - dragOffsetX)}px`;
    panel.style.top = `${Math.max(8, event.clientY - dragOffsetY)}px`;
  });
  window.addEventListener("mouseup", () => {
    dragOn = false;
  });

  if (!localStorage.getItem(storageKey)) {
    open();
  }
})();
