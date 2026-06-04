---
title: "About & methodology"
description: "What generation.forret.com is, how its generations, people and events are defined, and where the data comes from."
---

# About this site

**generation.forret.com** is a consolidated reference about the human generations,
from the **Lost Generation** (born 1883) to **Generation Alpha** (born through 2024).
For each generation it gathers, in one place, the things that are usually scattered
across many separate articles:

- the **birth-year range** that defines the cohort,
- the **notable people** born into it,
- the **memorable quotes** associated with it, and
- the **defining events** it lived through, with the age the cohort was at the time.

The goal is to be a clear, consistent, cross-generational reference — the angle that
single-topic encyclopaedia articles tend to fragment.

## How things are anchored to a generation

There is one core rule, and it depends on the **type** of thing:

- **People are anchored by birth year** ("born-in"). A person belongs to the generation
  whose range contains their year of birth. This drives every people list, the
  per-field breakdowns (actors, musicians, politicians, …) and the A–Z index.
- **Events are anchored by when they happened** ("lived-through"). An event is placed
  relative to each generation by the **age that generation was** when it occurred, not
  by who it was "about".

Year ranges are treated as **fixed and canonical** and are reused identically on every
page, so the same facts appear everywhere (no page contradicts another).

## Life stages

When we describe how old a generation was at an event, we use four plain-language
life stages based on age:

| Age | Life stage |
|---|---|
| 6–18 | school |
| 19–22 | college |
| 23–64 | working |
| 65+ | retired |

Because a generation spans about 15–20 birth years, at any given event its members fall
across a **range** of ages — so a stage may be shown as a span (for example
"school–working"). Events that happened before a person was **6 years old** are left out
of "what they lived through", on the basis that they are not personally remembered.

## Event importance (weight)

Each event carries an **importance score from 0 to 5**:

- **Weight 5** events are the defining milestones shown on every generation page
  ("how old they were when…").
- **Weight 4 and up** events are listed on each individual birth-year page.

This lets a small set of truly era-defining moments stand out from routine entries.

## Sources & accuracy

- Birth-year boundaries follow the widely-used consensus ranges; we deliberately do **not**
  invent new boundaries, so the site corroborates rather than contradicts the common
  understanding.
- Biographical context for people is drawn from public encyclopaedic sources
  (Wikipedia previews) and kept short.
- If you spot an error or omission, it is almost always a data issue that can be corrected
  at the source and regenerated.

## How the site is built

The pages you are reading are **static HTML**. They are generated from a structured
dataset (generations, people, events and quotes), exported to Markdown, and built into a
static site with [MkDocs](https://www.mkdocs.org/) + Material. There is no client-side
rendering: every page is plain, crawlable HTML, and the full set of pages is listed in
the auto-generated `sitemap.xml`.

Maintained by [Peter Forret](https://forret.com). Last reviewed 2026.
