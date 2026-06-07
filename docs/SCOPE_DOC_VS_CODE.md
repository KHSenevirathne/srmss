# Documentation vs Coding — scope split

The coursework has two very different kinds of deliverable. Knowing which is which tells you
what to *write* (your own analysis) versus what to *build* (the software), and stops you
treating the report as an afterthought — **60% of the marks are documentation and
presentation, only 30% is the code + testing.**

## A. DOCUMENTATION (written/analytical — this is your own work, not generated)

These are assessed as your understanding and writing. Claude Code can help with *structure,
formatting, and consistency checks*, but the analysis, decisions, and prose are yours.

| Deliverable | Marks | What it is |
|---|---|---|
| Report shell — title, abstract, table of contents, page numbers | 5% (G) | The document wrapper. |
| Methodology + project plan | 10% (G) | Pick an SDLC (e.g. Agile/Scrum — which you're using) and **justify why** it fits this project; Gantt/sprint plan, roles, milestones. |
| Requirements analysis & specification | 10% (G) | Functional + non-functional requirements, derived from the case study. |
| Software design | 20% (G) | **Use Case diagram, Class diagram, 3-Tier Architecture diagram**, interface (UI) design, + user manual in appendix. |
| Reflection | 10% (I) | ~400–500 words per person on the approach + teamwork: strengths and what to improve. **Write this yourself.** |
| Group presentation (Week 8) | 10% (G) | PowerPoint covering planning + requirements + design. |
| Individual recorded video | 5% (I) | You demo and explain *your own* implemented work. |

> The design diagrams are the contract the code must satisfy — build the software to match
> your Class/Use-Case diagrams, and draw the 3-Tier diagram to match the architecture in
> `CLAUDE.md` (Presentation / Application / Data).

## B. CODE (the software — build it, understand it, be able to demo it)

Assessed on the working system + tests. This is what `CLAUDE.md`'s roadmap drives.

| Deliverable | Marks | What it is |
|---|---|---|
| Implementation | 20% (I) | The Laravel app: all modules implemented per the UML design. |
| Testing | 10% (I) | Black-box and/or white-box testing of your implemented parts. |
| Working system on GitHub | (submission requirement) | Repo link; member IDs in file headers/README. |

## C. BRIDGE ARTIFACTS (produced from the code, but they live in the report)

Don't forget these — they're how the code earns documentation marks:

- **User manual** (appendix): screenshots + step-by-step "how to use", taken from the running app.
- **Test cases + results**: your black-box table (`docs/TEST_CASES.md`) and white-box test runs, written up in the report.
- **UI screenshots** for the design/interface section and the presentation.

## How they connect (the workflow)

```
Requirements (doc)  ─►  UML design (doc)  ─►  Code built to match (code)
                                              │
                          screenshots + tests ─┘ ─►  User manual + test results (doc)
```

Practically: finish enough **requirements + design** to present in Week 8, then build to that
design, then harvest screenshots/tests back into the report. The methodology, requirements,
diagrams, and reflection are the parts you must author yourself — and because there's a live
individual demo, you need to genuinely understand whatever code ends up in the repo. Check
your module's exact policy on AI assistance if you're unsure where the line sits.
