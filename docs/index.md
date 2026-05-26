---
layout: home

hero:
  name: Resting
  text: Typed REST resources for Laravel
  tagline: One source of truth for request parsing, validation, response shaping, and OpenAPI documentation.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/introduction
    - theme: alt
      text: Quickstart
      link: /guide/quickstart
    - theme: alt
      text: View on GitHub
      link: https://github.com/ebsp/resting

features:
  - icon: 🧱
    title: Typed fields
    details: Every property is a strongly-typed Field object that knows how to parse, validate, and format itself.
  - icon: ✅
    title: Composable validation
    details: Per-field validators, predicate-based conditional rules, and resource-level cross-field comparisons.
  - icon: 🔁
    title: Polymorphic resources
    details: UnionResource for tagged unions, DynamicResource for runtime-defined shapes.
  - icon: 📜
    title: OpenAPI 3 out of the box
    details: Generate a spec from your route collection — no extra annotations required.
  - icon: 🌿
    title: Laravel-native
    details: Auto-registered service provider, route macros, Eloquent helpers, and PHPUnit assertions.
  - icon: 🔒
    title: Strict by default
    details: Required-by-default fields, type-aware parsing, and a marshaller that surfaces nested error paths.
---
