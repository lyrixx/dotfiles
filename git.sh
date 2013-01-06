#!/bin/bash

git co prod
git rebase master
git co master
git push origin -f --all
git push github master
