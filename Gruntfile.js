module.exports = function(grunt) {
  require("phplint").gruntPlugin(grunt);

  grunt.initConfig({
    phplint: {
      options: {
        limit: 10,
        // phpCmd: "/home/scripts/php", // Defaults to php
        stdout: true,
        stderr: true,
        tmpDir: "./.cache/php" // Defaults to os.tmpDir()
      },
      files: ["src/**/*.php", "tests/**/*.php"]
    }
  });

  console.log("CHECKING SYNTAX ERROR...");

  grunt.registerTask("check", ["phplint"]);
};
