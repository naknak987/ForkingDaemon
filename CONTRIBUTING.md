# Git Flow

### Getting ready to do some work.

*  git checkout master
*  git pull origin/master
*  git checkout -b [issue|feature]-[issueNumber]

or

*  git fetch
*  git checkout -b [issue|feature]-[issueNumber] origin/master

Now we are ready to start working.

### Committing

You should make a commit anytime
*  You complete a unit of work 
*  You have changes you may want to undo

### Merging 

I'm the only one who can merge a branch back into master.
*  Push your branch up to the repo.
    *  git push --set-upstream origin [issue|feature]-[issueNumber]
*  Submit a merge request in the web interface.

### If you fall behind master

While you have your branch checked out
*  git fetch
*  git rebase origin/master
