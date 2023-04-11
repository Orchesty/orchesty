# Applinth Billing Processor

Codebase currently specialized to compute Applinth billing data with potential to be more generalized for generic cloud resources billing and flexible pricing specification.

## Notes

### Searchable keywords

* `todo:`
* `optimize:`

### Date-time handling

All Date(time) values are UTC based and computed using by native JS Date object (bad decision!),
honoring its specifics:

```
The JavaScript Date object specifically adheres to the concept of Unix Time (albeit with higher precision). This is part of the POSIX specification, and thus is sometimes called "POSIX Time". It does not count leap seconds, but rather assumes every day had exactly 86,400 seconds. You can read about this in section 20.3.1.1 of the current ECMAScript specification, which states:

https://www.ecma-international.org/ecma-262/9.0/index.html#sec-time-values-and-time-range
```

## Components

### Event Factory

Consumes raw events a returns an internal representation of the event in its latest form.

It accomplishes that by passing every raw event of any supported version through an upgrade chain of parsers/upgraders
that "migrates" the event up to the current representation.

### Parser

Parses a raw event of a specific version and returns the internal event form (as `ParsedResult`)
or upgrades it to the next version supported (as `UpgradedResult`). The Event Factory takes care of passing the
upgraded event to the next version of the parser in the upgrade chain.

Parsers should be super-simple plain functions that do not need to be touched when the codebase around them evolves.
In ideal case they are just added and the superseded versions changed to return `UpgradedResult`.
