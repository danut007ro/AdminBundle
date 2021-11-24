# Maker command

The bundle exposes a `make:dg_admin` command to be used with Symfony Maker Bundle. This command will generate all the boilerplate files to create an admin for a Doctrine entity. The entity name can be specified as an argument.

The command accepts the following options:

- `--crud` will also generate [create/update](table/cookbook_for_actioncolumn.md) Form, and methods in Controller
- `--filter` will also generate [filter](table/filters.md) Form
