import "../../Auth/style.css";
import Button from "../../../Button";
import Input from "../../../Input";
import { useNavigate } from "react-router-dom";
import { useRegisterForm } from "./logic.js";
// Logo is now served from public/images folder

const RegisterForm = ({ toggle }) => {
  const navigate = useNavigate();

  const {
    username,
    email,
    password,
    hobbies,
    preferences,
    bio,
    errorMessage,
    handleFieldChange,
    registerUser,
  } = useRegisterForm();

  return (
    <div className="auth-body">
      <div className="my-logo-container">
        <img src={MyLogo} alt="Logo" className="my-logo-img" />
        <h1>LEARNVENTURES</h1>
      </div>
      <div className="auth-container">
        <h1 className="auth-h1">
          <Button
            text={"←"}
            className="left-button"
            onClickListener={() => {
              navigate("/");
            }}
          />
          <span>Create Account</span>
        </h1>

        <form className="auth-form" onSubmit={registerUser}>
          <div>
            <label htmlFor="name" className="auth-label">
              Username
            </label>
            <Input
              type={"text"}
              name={"name"}
              hint={"Example"}
              required={true}
              className={"input-style"}
              minLength={3}
              maxLength={30}
              value={username}
              onChangeListener={(e) =>
                handleFieldChange("name", e.target.value)
              }
            />
          </div>

          <div>
            <label htmlFor="email" className="auth-label">
              Email
            </label>
            <Input
              type={"text"}
              name={"email"}
              hint={"email@example.com"}
              required={true}
              className={"input-style"}
              minLength={5}
              maxLength={100}
              value={email}
              onChangeListener={(e) =>
                handleFieldChange("email", e.target.value)
              }
            />
          </div>

          <div>
            <label htmlFor="password" className="auth-label">
              Password
            </label>
            <Input
              type={"password"}
              name={"password"}
              hint={"************"}
              required={true}
              className={"input-style"}
              minLength={8}
              maxLength={128}
              value={password}
              onChangeListener={(e) =>
                handleFieldChange("password", e.target.value)
              }
            />
          </div>

          <div>
            <label htmlFor="hobbies" className="auth-label">
              Hobbies
            </label>
            <Input
              type={"text"}
              name={"hobbies"}
              hint={"swimming, drawing"}
              required={true}
              className={"input-style"}
              minLength={5}
              maxLength={128}
              value={hobbies}
              onChangeListener={(e) =>
                handleFieldChange("hobbies", e.target.value)
              }
            />
          </div>

          <div>
            <label htmlFor="preferences" className="auth-label">
              Preferences
            </label>
            <Input
              type={"text"}
              name={"preferences"}
              hint={"learning style"}
              required={true}
              className={"input-style"}
              minLength={5}
              maxLength={128}
              value={preferences}
              onChangeListener={(e) =>
                handleFieldChange("preferences", e.target.value)
              }
            />
          </div>

          <div>
            <label htmlFor="bio" className="auth-label">
              Bio
            </label>
            <Input
              type={"text"}
              name={"bio"}
              hint={"about yourself"}
              required={true}
              className={"input-style"}
              minLength={8}
              maxLength={128}
              value={bio}
              onChangeListener={(e) => handleFieldChange("bio", e.target.value)}
            />
          </div>

          {errorMessage && (
            <strong className="auth-error">{errorMessage}</strong>
          )}

          <Button
            text={"Register"}
            className="auth-button"
            onClickListener={registerUser}
          />
        </form>

        <strong className="auth-link">
          Already have an account?{" "}
          <span className="auth-link-span" onClick={toggle}>
            Login
          </span>
        </strong>
      </div>
    </div>
  );
};

export default RegisterForm;
