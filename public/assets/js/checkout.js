/* Checkout destination city loader and simulated shipping estimate. */
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".checkout-form");
  if (!form) return;

  const countrySelect = document.getElementById("destinationCountry");
  const citySelect = document.getElementById("destinationCity");
  const shippingAmountEl = document.getElementById("checkoutShippingAmount");
  const distanceMetaEl = document.getElementById("checkoutDistanceMeta");
  const distanceEl = document.getElementById("checkoutDistance");
  const estimateMessageEl = document.getElementById("checkoutEstimateMessage");
  const summaryShippingEl = document.getElementById("checkoutShipping");
  const summaryTotalEl = document.getElementById("checkoutTotal");

  if (!countrySelect || !citySelect) return;

  const citiesUrl = form.dataset.citiesUrl || "";
  const estimateUrl = form.dataset.estimateUrl || "";
  const subtotal = Number.parseFloat(form.dataset.subtotal || "0") || 0;
  const initialCountry = (form.dataset.selectedCountry || "").trim();
  const initialCity = (form.dataset.selectedCity || "").trim();

  let citiesAbort = null;
  let estimateAbort = null;
  let citiesSeq = 0;
  let estimateSeq = 0;

  function formatUsd(amount) {
    const safe = Number.isFinite(amount) ? amount : 0;
    return `$${safe.toFixed(2)}`;
  }

  function formatNumber(value) {
    const safe = Number.isFinite(value) ? value : 0;
    return safe.toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function setEstimateMessage(message) {
    if (!estimateMessageEl) return;
    if (message) {
      estimateMessageEl.textContent = message;
      estimateMessageEl.hidden = false;
    } else {
      estimateMessageEl.textContent = "";
      estimateMessageEl.hidden = true;
    }
  }

  function resetEstimate(placeholderText = "Select a city to estimate") {
    if (shippingAmountEl) {
      shippingAmountEl.textContent = placeholderText;
    }
    if (distanceMetaEl) {
      distanceMetaEl.hidden = true;
    }
    if (distanceEl) {
      distanceEl.textContent = "";
    }
    if (summaryShippingEl) {
      summaryShippingEl.textContent = "Select a destination";
    }
    if (summaryTotalEl) {
      summaryTotalEl.textContent = formatUsd(subtotal);
    }
    setEstimateMessage("");
  }

  function setCityOptions(cities, placeholder, selectedId = "") {
    citySelect.innerHTML = "";

    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = placeholder;
    citySelect.appendChild(defaultOption);

    cities.forEach((city) => {
      const option = document.createElement("option");
      const id = String(city.id ?? "");
      const name = String(city.name ?? "");
      const region = typeof city.region === "string" ? city.region.trim() : "";

      option.value = id;
      option.textContent = region ? `${name} \u2014 ${region}` : name;
      if (selectedId !== "" && id === selectedId) {
        option.selected = true;
      }
      citySelect.appendChild(option);
    });
  }

  function buildUrl(base, params) {
    const url = new URL(base, window.location.origin);
    Object.entries(params).forEach(([key, value]) => {
      url.searchParams.set(key, value);
    });
    return url.toString();
  }

  async function loadCities(countryCode, preferredCityId = "") {
    if (citiesAbort) citiesAbort.abort();
    if (estimateAbort) estimateAbort.abort();

    const seq = ++citiesSeq;

    if (!countryCode || !citiesUrl) {
      setCityOptions([], "Choose a country first");
      citySelect.disabled = true;
      resetEstimate();
      return;
    }

    citySelect.disabled = true;
    setCityOptions([], "Loading cities...");
    resetEstimate();

    citiesAbort = new AbortController();

    try {
      const response = await fetch(buildUrl(citiesUrl, { country: countryCode }), {
        headers: { Accept: "application/json" },
        signal: citiesAbort.signal,
      });

      if (seq !== citiesSeq) return;

      const data = await response.json().catch(() => ({}));
      if (seq !== citiesSeq) return;

      if (!response.ok || !Array.isArray(data.cities)) {
        setCityOptions([], "Unable to load cities");
        citySelect.disabled = false;
        setEstimateMessage(
          typeof data.error === "string" && data.error
            ? data.error
            : "Could not load cities for that country. Please try again."
        );
        return;
      }

      if (data.cities.length === 0) {
        setCityOptions([], "No cities available");
        citySelect.disabled = true;
        setEstimateMessage("No supported cities were found for this country.");
        return;
      }

      setCityOptions(data.cities, "Select a city", preferredCityId);
      citySelect.disabled = false;

      if (citySelect.value !== "") {
        loadEstimate(countryCode, citySelect.value);
      }
    } catch (error) {
      if (error && error.name === "AbortError") return;
      if (seq !== citiesSeq) return;

      setCityOptions([], "Unable to load cities");
      citySelect.disabled = false;
      setEstimateMessage("Network error while loading cities. Please try again.");
    }
  }

  async function loadEstimate(countryCode, cityId) {
    if (estimateAbort) estimateAbort.abort();

    const seq = ++estimateSeq;

    if (!countryCode || !cityId || !estimateUrl) {
      resetEstimate();
      return;
    }

    if (!shippingAmountEl) return;

    setEstimateMessage("");
    shippingAmountEl.textContent = "Calculating...";
    if (distanceMetaEl) distanceMetaEl.hidden = true;

    estimateAbort = new AbortController();

    try {
      const response = await fetch(
        buildUrl(estimateUrl, { country: countryCode, city: cityId }),
        {
          headers: { Accept: "application/json" },
          signal: estimateAbort.signal,
        }
      );

      if (seq !== estimateSeq) return;

      const data = await response.json().catch(() => ({}));
      if (seq !== estimateSeq) return;

      if (!response.ok) {
        const message =
          typeof data.error === "string" && data.error
            ? data.error
            : "Unable to calculate shipping estimate.";

        if (response.status === 503) {
          resetEstimate("Unavailable");
        } else {
          resetEstimate("Estimate unavailable");
        }
        setEstimateMessage(message);
        return;
      }

      const distanceKm = Number.parseFloat(data.distance_km);
      const shippingUsd = Number.parseFloat(data.shipping_usd);

      if (!Number.isFinite(shippingUsd)) {
        resetEstimate("Estimate unavailable");
        setEstimateMessage("Received an invalid shipping estimate.");
        return;
      }

      const formattedShipping = formatUsd(shippingUsd);
      const formattedTotal = formatUsd(subtotal + shippingUsd);

      shippingAmountEl.textContent = formattedShipping;
      if (summaryShippingEl) {
        summaryShippingEl.textContent = formattedShipping;
      }
      if (summaryTotalEl) {
        summaryTotalEl.textContent = formattedTotal;
      }
      if (distanceEl && distanceMetaEl && Number.isFinite(distanceKm)) {
        distanceEl.textContent = formatNumber(distanceKm);
        distanceMetaEl.hidden = false;
      }
      setEstimateMessage("");
    } catch (error) {
      if (error && error.name === "AbortError") return;
      if (seq !== estimateSeq) return;

      resetEstimate("Estimate unavailable");
      setEstimateMessage("Network error while calculating shipping. Please try again.");
    }
  }

  countrySelect.addEventListener("change", () => {
    loadCities(countrySelect.value.trim());
  });

  citySelect.addEventListener("change", () => {
    loadEstimate(countrySelect.value.trim(), citySelect.value.trim());
  });

  const currentCountry = countrySelect.value.trim() || initialCountry;
  const currentCity = citySelect.value.trim() || initialCity;

  if (currentCountry === "") {
    citySelect.disabled = true;
  } else if (citySelect.options.length <= 1) {
    loadCities(currentCountry, currentCity);
  } else if (currentCity !== "") {
    loadEstimate(currentCountry, currentCity);
  }
});
