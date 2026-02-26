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

  const settingsButtons = root.querySelectorAll(".lc-settings-btn");
  const settingsPanels = root.querySelectorAll(".lc-settings-panel");
  const activateSettingsPanel = (target) => {
    if (!target) return;
    settingsButtons.forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.settingsTarget === target);
    });
    settingsPanels.forEach((panel) => {
      panel.classList.toggle("is-settings-active", panel.dataset.settingsPanel === target);
    });
  };
  settingsButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      activateTab("settings");
      activateSettingsPanel(btn.dataset.settingsTarget || "");
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

  const initialSettings = root.querySelector(".lc-settings-btn.is-active") || settingsButtons[0];
  if (initialSettings) {
    activateSettingsPanel(initialSettings.dataset.settingsTarget || "");
  }

  const runForm = root.querySelector(".lc-run-form");
  if (runForm) {
    const countrySelect = runForm.querySelector("select[name='country']");
    const stateSelect = runForm.querySelector("select[name='state']");
    const cityInput = runForm.querySelector("input[name='city']");
    const suggestionBox = runForm.querySelector(".lc-city-suggest-box");
    const cityCountryNote = runForm.querySelector(".lc-run-city-country-note");
    const countryScopeNote = runForm.querySelector(".lc-run-country-scope-note");
    const cityMapNode = runForm.querySelector(".lc-run-city-map-data");
    const fallbackMapNode = runForm.querySelector(".lc-run-city-fallback-data");
    const hierarchyNode = runForm.querySelector(".lc-run-location-hierarchy-data");
    let runCityMap = {};
    let fallbackCityMap = {};
    let locationHierarchy = {};
    try {
      runCityMap = JSON.parse(cityMapNode?.textContent || "{}");
    } catch (_err) {
      runCityMap = {};
    }
    try {
      fallbackCityMap = JSON.parse(fallbackMapNode?.textContent || "{}");
    } catch (_err) {
      fallbackCityMap = {};
    }
    try {
      locationHierarchy = JSON.parse(hierarchyNode?.textContent || "{}");
    } catch (_err) {
      locationHierarchy = {};
    }

    const toTitleCase = (value) =>
      value
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase())
        .trim();

    const allCountryNames = () =>
      [...new Set([...Object.keys(locationHierarchy), ...Object.keys(fallbackCityMap), ...Object.keys(runCityMap)])]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b));

    const stateOptions = (country) =>
      country && locationHierarchy[country] ? Object.keys(locationHierarchy[country]).sort((a, b) => a.localeCompare(b)) : [];

    const renderStateOptions = () => {
      if (!stateSelect) return;
      const country = (countrySelect?.value || "").trim();
      const selected = stateSelect.value || "";
      stateSelect.innerHTML = "";
      const base = document.createElement("option");
      base.value = "";
      base.textContent = country ? "All states/provinces" : "Select state/province (optional)";
      stateSelect.appendChild(base);
      stateOptions(country).forEach((state) => {
        const option = document.createElement("option");
        option.value = state;
        option.textContent = state;
        stateSelect.appendChild(option);
      });
      stateSelect.value = selected && [...stateSelect.options].some((o) => o.value === selected) ? selected : "";
      stateSelect.disabled = country === "" || stateOptions(country).length === 0;
    };

    const updateRunNotes = () => {
      const city = (cityInput?.value || "").trim();
      const country = (countrySelect?.value || "").trim();
      if (cityCountryNote) cityCountryNote.hidden = !(city && !country);
      if (countryScopeNote) countryScopeNote.hidden = !(country && !city);
    };

    const scoreCityMatch = (entry, query) => {
      const c = entry.city.toLowerCase();
      const q = query.toLowerCase().trim();
      if (!q) return 120;
      if (c === q) return 1000;
      if (c.startsWith(q)) return 700;
      if (c.includes(q)) return 450;
      let qi = 0;
      for (let i = 0; i < c.length && qi < q.length; i += 1) {
        if (c[i] === q[qi]) qi += 1;
      }
      return qi === q.length ? 250 : 0;
    };

    const collectMatches = (query) => {
      const selectedCountry = (countrySelect?.value || "").trim();
      const selectedState = (stateSelect?.value || "").trim();
      const map = {};
      const add = (country, state, city) => {
        const key = `${city}|${state}|${country}`;
        if (!map[key]) {
          const entry = { city, state, country };
          const score = scoreCityMatch(entry, query);
          if (score <= 0) return;
          map[key] = {
            ...entry,
            score:
              score +
              (selectedCountry && selectedCountry === country ? 120 : 0) +
              (selectedState && selectedState === state ? 90 : 0),
          };
        }
      };

      Object.keys(locationHierarchy).forEach((country) => {
        Object.keys(locationHierarchy[country] || {}).forEach((state) => {
          (locationHierarchy[country][state] || []).forEach((city) => add(country, state, toTitleCase(String(city || ""))));
        });
      });
      Object.keys(fallbackCityMap).forEach((country) => {
        (fallbackCityMap[country] || []).forEach((city) => add(country, "", toTitleCase(String(city || ""))));
      });
      Object.keys(runCityMap).forEach((country) => {
        (runCityMap[country] || []).forEach((city) => add(country, "", toTitleCase(String(city || ""))));
      });
      return Object.values(map)
        .filter((item) => {
          if (selectedCountry && item.country !== selectedCountry) return false;
          if (selectedState && item.state && item.state !== selectedState) return false;
          return true;
        })
        .sort((a, b) => b.score - a.score || a.city.localeCompare(b.city))
        .slice(0, 8);
    };

    const hideSuggestions = () => {
      if (!suggestionBox) return;
      suggestionBox.hidden = true;
      suggestionBox.innerHTML = "";
    };

    const showSuggestions = (items) => {
      if (!suggestionBox) return;
      suggestionBox.innerHTML = "";
      if (!items.length) {
        hideSuggestions();
        return;
      }
      items.forEach((item) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "lc-city-suggest-item";
        button.innerHTML = `<strong>${item.city}</strong><span>${item.state ? `${item.state}, ` : ""}${item.country}</span>`;
        button.addEventListener("mousedown", (event) => {
          event.preventDefault();
          if (cityInput) cityInput.value = item.city;
          if (countrySelect) countrySelect.value = item.country;
          renderStateOptions();
          if (stateSelect && item.state) stateSelect.value = item.state;
          updateRunNotes();
          hideSuggestions();
        });
        suggestionBox.appendChild(button);
      });
      suggestionBox.hidden = false;
    };

    const normalizeTypedCity = () => {
      if (!cityInput) return;
      cityInput.value = toTitleCase(cityInput.value || "");
    };

    if (countrySelect && allCountryNames().length > 0) {
      const existing = new Set(Array.from(countrySelect.options).map((opt) => opt.value));
      allCountryNames().forEach((country) => {
        if (!existing.has(country)) {
          const option = document.createElement("option");
          option.value = country;
          option.textContent = country;
          countrySelect.appendChild(option);
        }
      });
    }

    renderStateOptions();
    updateRunNotes();

    countrySelect?.addEventListener("change", () => {
      renderStateOptions();
      updateRunNotes();
      if (cityInput && cityInput.value.trim() !== "") {
        showSuggestions(collectMatches(cityInput.value));
      }
    });
    stateSelect?.addEventListener("change", () => {
      updateRunNotes();
      if (cityInput && cityInput.value.trim() !== "") {
        showSuggestions(collectMatches(cityInput.value));
      }
    });
    cityInput?.addEventListener("input", () => {
      updateRunNotes();
      showSuggestions(collectMatches(cityInput.value || ""));
    });
    cityInput?.addEventListener("focus", () => {
      showSuggestions(collectMatches(cityInput.value || ""));
    });
    cityInput?.addEventListener("blur", normalizeTypedCity);
    cityInput?.addEventListener("blur", () => {
      setTimeout(() => hideSuggestions(), 120);
    });
  }

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
      selector: "#lc-section-run-checklist",
      text: "Run checklist: review compliance requirements in Settings. The system automatically validates these rules when you queue a run.",
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
        if (step.selector === "#lc-section-run-checklist") {
          activateSettingsPanel("run-checklist");
        }
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
